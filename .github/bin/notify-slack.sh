#!/bin/bash
set +e

if [ "$DO_NOTIFY" == "Yes" ]; then
# Notify Slack of site creation start
SLACK_START="------------- :building_construction: Creating new site - ${SITE_LABEL}  :building_construction: ------------- \n"
echo -e "Starting ${SITE_LABEL}"

curl -X POST "https://slack.com/api/chat.postMessage" \
	-H "Authorization: Bearer $SLACK_BOT_TOKEN" \
	-H "Content-Type: application/json" \
	-d "{
	\"channel\": \"$SLACK_CHANNEL\",
	\"text\": \"$SLACK_START\"
	}"
fi

terminus site:create --org $ORG_UUID --region $REGION -- $SITE_NAME "${SITE_LABEL}" $UPSTREAM_UUID
terminus tag:add $SITE_NAME $ORG_UUID GHA

DASHBOARD=$(terminus dashboard:view $SITE_NAME --print)
SLACK="${SITE_LABEL} Site Creation Complete. ${DASHBOARD}"

if [ "$DO_NOTIFY" == "Yes" ]; then
curl -X POST "https://slack.com/api/chat.postMessage" \
	-H "Authorization: Bearer $SLACK_BOT_TOKEN" \
	-H "Content-Type: application/json" \
	-d "{
	\"channel\": \"$SLACK_CHANNEL\",
	\"text\": \"$SLACK\"
	}"
fi