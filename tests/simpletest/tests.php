<?php

/**
 * PHPPgAdmin v6.0.0-beta.51
 */
require_once __DIR__.'/../../src/lib.inc.php';
require_once __DIR__.'/../../src/lang/english.php';

require_once 'config.tests.php';

set_include_path($PHP_SIMPLETEST_HOME.':'.'./testcase'.':'.get_include_path());

$run = true;

if ($run) {
    require_once 'testcase/testphpPgAdminMain.php';
} else {
    echo '<pre>';
    print_r([
        'webUrl'               => $webUrl,
        'SERVER'               => $SERVER,
        'SUPER_USER_NAME'      => $SUPER_USER_NAME,
        'SUPER_USER_PASSWORD'  => $SUPER_USER_PASSWORD,
        'POWER_USER_NAME'      => $POWER_USER_NAME,
        'POWER_USER_PASSWORD'  => $POWER_USER_PASSWORD,
        'NORMAL_USER_NAME'     => $NORMAL_USER_NAME,
        'NORMAL_USER_PASSWORD' => $NORMAL_USER_PASSWORD,
        'PHP_SIMPLETEST_HOME'  => $PHP_SIMPLETEST_HOME,
        'PHP_SELF'             => $_SERVER['PHP_SELF'],
        'include path'         => get_include_path(),
    ]);
    echo '</pre>';
}
