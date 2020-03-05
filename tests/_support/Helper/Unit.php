<?php

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
 */

namespace Helper;

use PHPPgAdmin\ContainerUtils;

if (!is_readable(ContainerUtils::BASE_PATH . '/src/lib.inc.php')) {
    die('lib.inc.php is not readable');
}
defined('IN_TEST') || define('IN_TEST', true);
require_once ContainerUtils::BASE_PATH . '/src/lib.inc.php';
// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
    /** @var string */
    const BASE_PATH = ContainerUtils::BASE_PATH;
    /** @var string */
    const SUBFOLDER = ContainerUtils::SUBFOLDER;
    /** @var string */
    const DEBUGMODE = ContainerUtils::DEBUGMODE;
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
            self::$_container = ContainerUtils::getContainerInstance();
        }

        //dump(PHP_SAPI);

        //\Codeception\Util\Debug::debug('BASE_PATH is ' . \BASE_PATH);

        return self::$_container;
    }
}
