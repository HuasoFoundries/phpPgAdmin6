<?php

#declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 */

use Kint\Kint;
use Kint\Renderer\CliRenderer;
use Kint\Renderer\RichRenderer;

define('KINT_SKIP_FACADE', true);


    Kint::$enabled_mode   = true;
    RichRenderer::$folder = false;

    if (!\function_exists('dump')) {
        function dump(...$vars)
        {
            $fp = fopen(sprintf('%s/temp/debug.output.log', dirname(__DIR__)), 'ab');

            CliRenderer::$cli_colors = true;
            $return                  = Kint::$return;
            $enabled_mode            = Kint::$enabled_mode;
            Kint::$return            = true;
            Kint::$enabled_mode      = Kint::MODE_CLI;

            $kintdump = Kint::dump(...$vars);
            //dump($kintdump);
            fwrite($fp, $kintdump);

            Kint::$enabled_mode = $enabled_mode;
            Kint::$return       = $return;

            fclose($fp);
        }
    }

    function ddd(...$v): void
    {
        Kint::dump(...$v);

        exit;
    }

    Kint::$aliases[] = ['PHPPgAdmin\\Traits\\HelperTrait', 'staticTrace'];
    Kint::$aliases[] = ['PHPPgAdmin\\ContainerUtils', 'staticTrace'];
    Kint::$aliases[] = ['PHPPgAdmin\\Traits\\HelperTrait', 'prTrace'];
    Kint::$aliases[] = 'ddd';
    Kint::$aliases[] = 'dump';
