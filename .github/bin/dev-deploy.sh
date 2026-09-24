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

get_channel_id() {
  local NAME="$1"
  curl -s -X GET "https://slack.com/api/conversations.list?exclude_archived=true&limit=1000" \
    -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
    -H "Content-Type: application/x-www-form-urlencoded" | \
    jq -r --arg name "$NAME" '.channels[] | select(.name == ($name | ltrimstr("#"))) | .id'
}

# Set Slack variables
SLACK_BOT_TOKEN="${SLACK_BOT_TOKEN}"
SLACK_CHANNEL_NAME="#firehose"
SLACK_CHANNEL_ID=$(get_channel_id "$SLACK_CHANNEL_NAME")

# Create initial Slack message with blocks and return timestamp
slack_start_message() {
  local SITE="$1"
  local START_TIME=$(date +%s)
  local SITE_LINK="https://dev-${SITE}.pantheonsite.io"

  mkdir -p .slack-ts
  echo "$START_TIME" > .slack-ts/${SITE}.start

  local PAYLOAD=$(jq -n \
    --arg channel "$SLACK_CHANNEL_ID" \
    --arg emoji ":building_construction:" \
    --arg site "$SITE" \
    --arg site_link "$SITE_LINK" \
    '{
      channel: $channel,
      attachments: [
        {
          color: "#FFDC28",
          blocks: [
            {
              type: "header",
              text: { type: "plain_text", text: "\($emoji) Starting \($site) Deployment" }
            },
            {
              type: "section",
              fields: [
                { type: "mrkdwn", text: "*Environment:* Dev" },
                { type: "mrkdwn", text: "*Site:* <\($site_link)|\($site)>" }
              ]
            },
            { type: "divider" }
          ]
        }
      ]
    }'
  )

  RESPONSE=$(curl -s -X POST https://slack.com/api/chat.postMessage \
  -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
  -H "Content-Type: application/json; charset=utf-8" \
  -d "$PAYLOAD")

  TS=$(echo "$RESPONSE" | jq -r '.ts')
  echo "$TS" > .slack-ts/${SITE}.ts
}

# Send a message into the thread for the given site
slack_thread_update() {
  local SITE="$1"
  local MESSAGE="$2"
  local TS=$(cat .slack-ts/${SITE}.ts)

  jq -n \
    --arg channel "$SLACK_CHANNEL_ID" \
    --arg text "$MESSAGE" \
    --arg ts "$TS" \
    '{
      channel: $channel,
      text: $text,
      thread_ts: $ts
    }' | curl -s -X POST https://slack.com/api/chat.postMessage \
      -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
      -H "Content-Type: application/json; charset=utf-8" \
      -d @-
}

# Update the original message with the final state
slack_update_final() {
  local SITE="$1"
  local TS=$(cat .slack-ts/${SITE}.ts)
  local START_TIME=$(cat .slack-ts/${SITE}.start)
  local END_TIME=$(date +%s)
  local DURATION=$((END_TIME - START_TIME))
  local MIN=$(printf "%.2f" "$(bc <<< "scale=2; $DURATION/60")")
  local LINK="https://dev-${SITE}.panthsonsite.io"

  local PAYLOAD=$(jq -n \
    --arg channel "$SLACK_CHANNEL_ID" \
    --arg ts "$TS" \
    --arg emoji ":white_check_mark:" \
    --arg site "$SITE" \
    --arg min "$MIN" \
    --arg link "$LINK" \
    '{
      channel: $channel,
      ts: $ts,
      attachments: [
        {
          color: "#2EB67D",
          blocks: [
            {
              type: "header",
              text: { type: "plain_text", text: "\($emoji) Deployment Complete! :tea:" }
            },
            {
              type: "section",
              fields: [
                { type: "mrkdwn", text: "*Environment:* Dev" },
                { type: "mrkdwn", text: "*Site:* <\($link)|\($site)>" }
              ]
            },
            {
              type: "section",
              text: { type: "mrkdwn", text: "Completed in \($min)min.\n<\($link)|View site>" }
            },
            { type: "divider" }
          ]
        }
      ]
    }'
  )

  curl -s -X POST https://slack.com/api/chat.update \
    -H "Authorization: Bearer $SLACK_BOT_TOKEN" \
    -H "Content-Type: application/json; charset=utf-8" \
    -d "$PAYLOAD"  
}

[ "$NOTIFY" == "Yes" ] && slack_start_message "$SITE"

echo -e "Starting ${SITE} \n"

# Backup DB prior to deploy, 30-day retention
if [ "$BACKUP" == "Yes" ]; then
  terminus backup:create --element database --keep-for 30 -- $DEV
  SLACK_BACKUP="Finished ${SITE_LABEL} Dev Backup. Deploying code."
  [ "$NOTIFY" == "Yes" ] && slack_thread_update "$SITE" "$SLACK_BACKUP"
fi

# upstream:updates:apply requires the environment to be in git connection mode
# and fails immediately (silently, since -q suppresses the error output) if the
# environment is in SFTP mode. Check the mode first and flip it if needed. The
# restore is registered via a trap (rather than just running after the apply
# call) so the site still gets flipped back to SFTP even if the apply step
# itself fails and set -e exits the script early.
CONNECTION_MODE=$(terminus env:info $DEV --field=connection_mode)
WAS_SFTP=false

restore_connection_mode() {
  if [ "$WAS_SFTP" == true ]; then
    echo "Restoring ${DEV} to SFTP mode."
    terminus --yes connection:set $DEV sftp
  fi
}

if [ "$CONNECTION_MODE" == "sftp" ]; then
  WAS_SFTP=true
  trap restore_connection_mode EXIT
  echo "Environment ${DEV} is in SFTP mode. Switching to Git mode to apply upstream updates."
  terminus --yes connection:set $DEV git
fi

# Apply upstream updates
terminus upstream:updates:apply $DEV --accept-upstream -q
echo -e "Finished applying upstream updates for ${SITE} \n"

SLACK_DEPLOY="${SITE_LABEL} DEV Code Deployment Finished. Importing config and clearing cache."
[ "$NOTIFY" == "Yes" ] && slack_thread_update "$SITE" "$SLACK_DEPLOY"

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
[ "$NOTIFY" == "Yes" ] && slack_update_final "$SITE"

exit 0  # Done!
