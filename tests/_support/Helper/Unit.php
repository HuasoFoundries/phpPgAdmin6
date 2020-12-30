<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace Helper;

\defined('IN_TEST') || \define('IN_TEST', true);

$bootstrapfile = \dirname(__DIR__, 3) . '/src/lib.inc.php';

if (!\is_readable($bootstrapfile)) {
    die('lib.inc.php is not readable');
}

require_once $bootstrapfile;
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
     * @var \PHPPgAdmin
     */
    private static $_container;

    private static $_conf;

    public static function getContainer()
    {
        //$conf = self::getConf();
        if (!static::$_container) {
            self::$_container = containerInstance();
        }

        //dump(PHP_SAPI);

        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);

        return self::$_container;
    }
}
