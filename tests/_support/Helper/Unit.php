<?php

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Unit extends \Codeception\Module
{
    const DIRNAME = __DIR__;

    private static $container;

    public static function getDir()
    {
        return self::DIRNAME;
    }

    public static function getContainer()
    {
        if (!static::$container) {
            require_once self::DIRNAME.'/../../../src/lib.inc.php';
            self::$container = $container;
        }

        return self::$container;
    }
}
