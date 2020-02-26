<?php

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

namespace Helper;

use PHPPgAdmin\ContainerUtils;

defined('BASE_PATH') || define('BASE_PATH', dirname(dirname(dirname(__DIR__))));
defined('SUBFOLDER') || define(
    'SUBFOLDER',
    str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', BASE_PATH)
);
defined('DEBUGMODE') || define('DEBUGMODE', false);
if (!is_readable(BASE_PATH . '/src/lib.inc.php')) {
    die('lib.inc.php is not readable');
}
defined('IN_TEST') || define('IN_TEST', true);
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
    /** @var string */
    const BASE_PATH = BASE_PATH;
    /** @var string */
    const SUBFOLDER = SUBFOLDER;
    /** @var string */
    const DEBUGMODE = DEBUGMODE;
    /**
     * @var \PHPPgAdmin
     */
    private static $_container;
    private static $_conf;

    public static function getDir()
    {
        return self::DIRNAME;
    }

    public static function getContainer()
    {

        //$conf = self::getConf();
        if (!static::$_container) {
            require_once BASE_PATH . '/src/lib.inc.php';
            self::$_container = ContainerUtils::getContainerInstance();
        }

        //dump(PHP_SAPI);

        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);

        return self::$_container;
    }
}
