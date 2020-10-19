<?php

// declare(strict_types = 1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 */

// to enable debugging middleware and endpoints
// debuggers
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use Symfony\Component\VarDumper\Dumper\ContextProvider\CliContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\ServerDumper;
use Symfony\Component\VarDumper\VarDumper;

if (\class_exists('Symfony\Component\VarDumper\VarDumper')) {
    $cloner         = new VarCloner();
    $fallbackDumper = \in_array(\PHP_SAPI, ['cli', 'phpdbg'], true) ? new CliDumper() : new HtmlDumper();
    $dumper         = new ServerDumper('tcp://127.0.0.1:9912', $fallbackDumper, [
        'cli'    => new CliContextProvider(),
        'source' => new SourceContextProvider(),
    ]);

    VarDumper::setHandler(function ($var) use ($cloner, $dumper): void {
        $dumper->dump($cloner->cloneVar($var));
    });
}
if (false && \PHP_SAPI !== 'cli') {
    \define('SESSION_SAVE_PATH', \implode(\DIRECTORY_SEPARATOR, [BASE_PATH, 'temp/sessions']));
    \defined('IN_TEST') || \define('IN_TEST', false);

    if (!IN_TEST && \defined('SESSION_SAVE_PATH')
        && (
            \is_dir(SESSION_SAVE_PATH)
            || \mkdir(SESSION_SAVE_PATH, 0777, true)
        )
    ) {
        \session_save_path(SESSION_SAVE_PATH);
    }
}
