#!/bin/bash

###
# Execute the Behat test suite against a prepared Pantheon site environment.
###

set -ex

SELF_DIRNAME="`dirname -- "$0"`"

# Require a target site
if [ -z "$TERMINUS_SITE" ] || [ -z "$TERMINUS_ENV" ]; then
	echo "TERMINUS_SITE and TERMINUS_ENV environment variables must be set"
	exit 1
fi

# Require admin username and password
if [ -z "$WORDPRESS_ADMIN_USERNAME" ] || [ -z "$WORDPRESS_ADMIN_PASSWORD" ]; then
	echo "WORDPRESS_ADMIN_USERNAME and WORDPRESS_ADMIN_PASSWORD environment variables must be set"
	exit 1
fi

# Manually load Behat and Mink PHAR files via a bootstrap file
export BEHAT_EXTENSIONS_DIR="$HOME/behat-extensions"
export BEHAT_PARAMS='{"extensions" : {"Behat\\MinkExtension" : {"base_url" : "http://'$TERMINUS_ENV'-'$TERMINUS_SITE'.pantheonsite.io"} }}'

# Run Behat with explicit bootstrap file
cd $SELF_DIRNAME && php "${WORKSPACE_DIR}/.github/tests/bootstrap.php" && behat --config="${WORKSPACE_DIR}/.github/tests/behat.yml" $*
