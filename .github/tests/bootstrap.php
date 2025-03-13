<?php
// Manually load Behat and Mink PHAR files
$pharPath = getenv('HOME') . '/behat-extensions';

require_once "$pharPath/Mink.phar";
require_once "$pharPath/MinkExtension.phar";
require_once "$pharPath/MinkSelenium2Driver.phar";
require_once "$pharPath/MinkGoutteDriver.phar";
