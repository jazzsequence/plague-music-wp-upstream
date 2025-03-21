#!/bin/bash

# Usage
# ./dev-deploy.sh <site-name or uuid>

# Exit on error
set -e

SITE=$1
DEV=$(echo "${SITE}.dev")
START=$SECONDS
SITE_LABEL=$(terminus site:info --fields label --format string -- ${SITE})
BACKUP=$DO_BACKUP
NOTIFY=$DO_NOTIFY
VERBOSE=$VERBOSE

# Set Slack variables
SLACK_BOT_TOKEN="${SLACK_BOT_TOKEN}"
SLACK_CHANNEL="${SLACK_CHANNEL:-#general}"  # Default to #general if not set

# Function to send messages to Slack
send_slack_message() {
  local MESSAGE="$1"
  if [ "$VERBOSE" == "Yes" ]; then
    echo "Sending message to Slack: $MESSAGE"
    curl -X POST "https://slack.com/api/chat.postMessage" \
      -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
      -H "Content-Type: application/json" \
      -d "{
        \"channel\": \"$SLACK_CHANNEL\",
        \"text\": \"$MESSAGE\"
      }"
  else
    curl -X POST "https://slack.com/api/chat.postMessage" \
      -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
      -H "Content-Type: application/json" \
      -d "{
        \"channel\": \"$SLACK_CHANNEL\",
        \"text\": \"$MESSAGE\"
      }" > /dev/null 2>&1
  fi
}

# Notify Slack that deployment is starting
SLACK_START=":building_construction: Started ${SITE_LABEL} deployment to Dev :building_construction: \n"
[ "$NOTIFY" == "Yes" ] && send_slack_message "$SLACK_START"

echo -e "Starting ${SITE} \n"

# Backup DB prior to deploy, 30-day retention
if [ "$BACKUP" == "Yes" ]; then
  terminus backup:create --element database --keep-for 30 -- $DEV
  SLACK_BACKUP="Finished ${SITE_LABEL} Dev Backup. Deploying code."
  [ "$NOTIFY" == "Yes" ] && send_slack_message "$SLACK_BACKUP"
fi

# Apply upstream updates
terminus upstream:updates:apply $DEV --accept-upstream -q
echo -e "Finished applying upstream updates for ${SITE} \n"

SLACK_DEPLOY="${SITE_LABEL} DEV Code Deployment Finished. Importing config and clearing cache."
[ "$NOTIFY" == "Yes" ] && send_slack_message "$SLACK_DEPLOY"

# Run any post-deploy commands
if [ "$VERBOSE" == "Yes" ]; then
  terminus env:clear-cache $DEV -vvv
else
  terminus env:clear-cache $DEV
fi
echo -e "Finished clearing cache for ${SITE} \n"

# Report time to results
DURATION=$(( SECONDS - START ))
TIME_DIFF=$(bc <<< "scale=2; $DURATION / 60")
MIN=$(printf "%.2f" $TIME_DIFF)
SITE_LINK="https://dev-${SITE}.pantheonsite.io"
SLACK_FINISH=":white_check_mark: Finished ${SITE_LABEL} deployment to Dev in ${MIN} minutes. \n ${SITE_LINK}"
[ "$NOTIFY" == "Yes" ] && send_slack_message "$SLACK_FINISH"

exit 0  # Done!
