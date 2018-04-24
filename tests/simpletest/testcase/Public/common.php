<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

// The home page of phpPgAdmin.
global $webUrl;
$webUrl = 'http://localhost/phpPgAdmin';

// The user name of phpPgAdmin.
// "root" is the superuser (createdb and create user).
// "tester" is the power user (createdb).
// "guest" is the normal user.
global $SUPER_USER_NAME;
$SUPER_USER_NAME = 'super';

global $POWER_USER_NAME;
$POWER_USER_NAME = 'tester';

global $NORMAL_USER_NAME;
$NORMAL_USER_NAME = 'guest';

//The password for these users.
global $SUPER_USER_PASSWORD;
$SUPER_USER_PASSWORD = 'super';

global $POWER_USER_PASSWORD;
$POWER_USER_PASSWORD = 'tester';

global $NORMAL_USER_PASSWORD;
$NORMAL_USER_PASSWORD = 'guest';
