<?php
$will_redirect = false;
$req_uri       = $_SERVER['REQUEST_URI'];
if (substr($_SERVER['REQUEST_URI'], 0, 10) === '/index.php') {
    $will_redirect = true;
    $req_uri       = substr($req_uri, 10);
}

$filePath = realpath(ltrim($req_uri, '/'));

if ($filePath && is_file($filePath)) {
    // 1. check that file is not outside of this directory for security
    // 2. check for circular reference to router.php
    // 3. don't serve dotfiles

    if (strpos($filePath, __DIR__ . DIRECTORY_SEPARATOR) === 0 &&
        $filePath != __DIR__ . DIRECTORY_SEPARATOR . 'index.php' &&
        substr(basename($filePath), 0, 1) != '.'
    ) {
        if (strtolower(substr($filePath, -4)) == '.php') {
            // php file; serve through interpreter
            include $filePath;
            return;
        } else if ($will_redirect) {
            $new_location = 'Location: http://' . $_SERVER['HTTP_HOST'] . $req_uri;

            header($new_location, 301);
            return;
        } else {
            // asset file; serve from filesystem
            return false;
        }
    } else {
        // disallowed file
        header('HTTP/1.1 404 Not Found');
        echo '404 Not Found';
    }
}
