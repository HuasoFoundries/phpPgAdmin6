<?php

declare(strict_types=1);

/**
 * PHPPgAdmin 6.1.0
 */

// This section is made to be able to parse requests coming from PHP Builtin webserver
if (\PHP_SAPI === 'cli-server') {
    $will_redirect = false;
    /** @todo is PHP_SELF is not set, chances are REQUEST_URI won't either */
    $req_uri = $_SERVER['PHP_SELF'] ?? $_SERVER['REQUEST_URI'];

    if ('/index.php' === \mb_substr($req_uri, 0, 10)) {
        $will_redirect = true;
        $req_uri = \mb_substr($req_uri, 10);
    }
    $filePath = \realpath(\ltrim($req_uri, '/'));
    $new_location = 'Location: http://' . $_SERVER['HTTP_HOST'] . $req_uri;

    if ($filePath && // 1. check that filepath is set
        \is_readable($filePath) && // 2. and references a readable file/folder
        0 === \mb_strpos($filePath, BASE_PATH . \DIRECTORY_SEPARATOR) && // 3. And is inside this folder
        BASE_PATH . \DIRECTORY_SEPARATOR . 'index.php' !== $filePath && // 4. discard circular references to index.php
        '.' !== \mb_substr(\basename($filePath), 0, 1) // 5. don't serve dotfiles
    ) {
        if ('.php' === \mb_strtolower(\mb_substr($filePath, -4))) {
            // php file; serve through interpreter
            include $filePath;

            return;
        }

        if ($will_redirect) {
            \header($new_location, true, 301);

            return;
        }
        // asset file; serve from filesystem
        return false;
    }
}

require_once __DIR__ . '/src/router.php';
