<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */
global $webUrl;
global $SERVER;
global $SUPER_USER_NAME;
global $SUPER_USER_PASSWORD;
global $POWER_USER_NAME;
global $POWER_USER_PASSWORD;
global $NORMAL_USER_NAME;
global $NORMAL_USER_PASSWORD;
global $PHP_SIMPLETEST_HOME;

//$webUrl              = 'http://localhost:8000/index.php';
$webUrl              = 'http://phppga.local';
$SERVER              = sha1("{$conf['servers'][0]['host']}:{$conf['servers'][0]['port']}:{$conf['servers'][0]['sslmode']}");
$DATABASE            = 'ppatests';
$PHP_SIMPLETEST_HOME = dirname(dirname(__DIR__)).'/vendor/simpletest/simpletest';

$SUPER_USER_NAME     = 'ppatests_super';
$SUPER_USER_PASSWORD = 'super';

$POWER_USER_NAME     = 'ppatests_power';
$POWER_USER_PASSWORD = 'power';

$NORMAL_USER_NAME     = 'ppatests_guest';
$NORMAL_USER_PASSWORD = 'guest';
