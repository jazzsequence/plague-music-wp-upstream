#!/bin/bash

# Echo commands as they are executed, but don't allow errors to stop the script.
set -x

if [ -z "$TERMINUS_SITE" ]; then
  echo "TERMINUS_SITE environment variables must be set"
  exit 1
fi

# Only delete old environments if there is a pattern defined to
# match environments eligible for deletion. Otherwise, delete the
# current multidev environment immediately.
#
# To use this feature, set MULTIDEV_DELETE_PATTERN to '^ci-' or similar
# in the CI server environment variables.
if [ -z "$MULTIDEV_DELETE_PATTERN" ] ; then
  if [ -z "$TERMINUS_ENV" ]; then
    echo "TERMINUS_ENV environment variables must be set if you're not using MULTIDEV_DELETE_PATTERN"
    exit 1
  fi
  terminus env:delete $TERMINUS_SITE.$TERMINUS_ENV --delete-branch --yes
  exit 0
fi

# List all but the newest two environments.
ALL_ENVS=$(terminus env:list "$TERMINUS_SITE" --format=list)
OLDEST_ENVIRONMENTS=$(echo "$ALL_ENVS" \
  | grep -v dev \
  | grep -v test \
  | grep -v live \
  | sort \
  | grep "$MULTIDEV_DELETE_PATTERN" \
  | sed -e '$d')

# Exit if there are no environments to delete
if [ -z "$OLDEST_ENVIRONMENTS" ] ; then
  exit 0
fi

# Go ahead and delete the oldest environments.
for ENV_TO_DELETE in $OLDEST_ENVIRONMENTS ; do
  terminus env:delete $TERMINUS_SITE.$ENV_TO_DELETE --delete-branch --yes

  # Delete related GitHub deployment environment
  if [ -n "$GITHUB_REPOSITORY" ]; then
    echo "Deleting GitHub deployment environment: $ENV_TO_DELETE..."
    if ! gh api "repos/${GITHUB_REPOSITORY}/environments/$ENV_TO_DELETE" > /dev/null 2>&1; then
      echo "GitHub environment $ENV_TO_DELETE does not exist, skipping deletion."
      continue
    fi
    gh api --method DELETE "repos/${GITHUB_REPOSITORY}/environments/$ENV_TO_DELETE"
  else 
    echo "Skipping GitHub deletion for $ENV_TO_DELETE — GITHUB_TOKEN or GITHUB_REPOSITORY not set."
  fi
done
