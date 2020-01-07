<?php

/**
 * PHPPgAdmin v6.0.0-RC2
 */
require_once __DIR__.'/src/lib.inc.php';

// This section is made to be able to parse requests coming from PHP Builtin webserver
if (PHP_SAPI === 'cli-server') {
    $will_redirect = false;
    // @todo is PHP_SELF is not set, chances are REQUEST_URI won't either
    $req_uri = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['REQUEST_URI'];
    if (substr($req_uri, 0, 10) === '/index.php') {
        $will_redirect = true;
        $req_uri       = substr($req_uri, 10);
    }
    $filePath     = realpath(ltrim($req_uri, '/'));
    $new_location = 'Location: http://'.$_SERVER['HTTP_HOST'].$req_uri;

    if ($filePath && // 1. check that filepath is set
        is_readable($filePath) && // 2. and references a readable file/folder
        strpos($filePath, BASE_PATH.DIRECTORY_SEPARATOR) === 0 && // 3. And is inside this folder
        $filePath != BASE_PATH.DIRECTORY_SEPARATOR.'index.php' && // 4. discard circular references to index.php
        substr(basename($filePath), 0, 1) != '.' // 5. don't serve dotfiles
    ) {
        if (strtolower(substr($filePath, -4)) == '.php') {
            // php file; serve through interpreter
            include $filePath;

            return;
        }
        if ($will_redirect) {
            header($new_location, true, 301);

            return;
        }
        // asset file; serve from filesystem
        return false;
    }
}

$app->get('/status', function (
    /* @scrutinizer ignore-unused */
    $request,
    /* @scrutinizer ignore-unused */
    $response,
    /* @scrutinizer ignore-unused */
    $args
) {
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withJson(['version' => $this->version]);
});

$app->post('/redirect/server', function (
    /* @scrutinizer ignore-unused */
    $request,
    /* @scrutinizer ignore-unused */
    $response,
    /* @scrutinizer ignore-unused */
    $args
) {
    $body = $response->getBody();
    $misc = $this->misc;

    $loginShared   = $request->getParsedBodyParam('loginShared');
    $loginServer   = $request->getParsedBodyParam('loginServer');
    $loginUsername = $request->getParsedBodyParam('loginUsername');
    $loginPassword = $request->getParsedBodyParam('loginPassword_'.md5($loginServer));

    // If login action is set, then set session variables
    if ((bool) $loginServer && (bool) $loginUsername && $loginPassword !== null) {
        $_server_info = $this->misc->getServerInfo($loginServer);

        $_server_info['username'] = $loginUsername;
        $_server_info['password'] = $loginPassword;

        $this->misc->setServerInfo(null, $_server_info, $loginServer);

        $data = $misc->getDatabaseAccessor();

        if ($data === null) {
            $login_controller = new \PHPPgAdmin\Controller\LoginController($this, true);
            $body->write($login_controller->doLoginForm($misc->getErrorMsg()));

            return $response;
        }
        // Check for shared credentials
        if ($loginShared !== null) {
            $_SESSION['sharedUsername'] = $loginUsername;
            $_SESSION['sharedPassword'] = $loginPassword;
        }

        $misc->setReloadBrowser(true);

        $destinationurl = $this->utils->getDestinationWithLastTab('alldb');

        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }
    $_server_info = $this->misc->getServerInfo();

    if (!isset($_server_info['username'])) {
        $destinationurl = $this->utils->getDestinationWithLastTab('server');

        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }
});

$app->get('/redirect[/{subject}]', function (
    /* @scrutinizer ignore-unused */
    $request,
    /* @scrutinizer ignore-unused */
    $response,
    /* @scrutinizer ignore-unused */
    $args
) {
    //ddd($_SESSION);
    $subject        = (isset($args['subject'])) ? $args['subject'] : 'root';
    $destinationurl = $this->utils->getDestinationWithLastTab($subject);

    return $response->withStatus(302)->withHeader('Location', $destinationurl);
});

$app->map(['GET', 'POST'], '/src/views/{subject}', function (
    /* @scrutinizer ignore-unused */
    $request,
    /* @scrutinizer ignore-unused */
    $response,
    /* @scrutinizer ignore-unused */
    $args
) {
    $subject = $args['subject'];
    if ($subject === 'server') {
        $subject = 'servers';
    }
    //$this->utils->dump($request->getParams());
    $_server_info = $this->misc->getServerInfo();

    $safe_subjects = ($subject === 'servers' || $subject === 'intro' || $subject === 'browser');

    if ($this->misc->getServerId() === null && !$safe_subjects) {
        return $response->withStatus(302)->withHeader('Location', SUBFOLDER.'/src/views/servers');
    }

    if (!isset($_server_info['username']) && $subject !== 'login' && !$safe_subjects) {
        $destinationurl = SUBFOLDER.'/src/views/login?server='.$this->misc->getServerId();

        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }

    $className  = '\PHPPgAdmin\Controller\\'.ucfirst($subject).'Controller';
    $controller = new $className($this);

    return $controller->render();
});

$app->get('/{subject:\w+}', function (
    /* @scrutinizer ignore-unused */
    $request,
    /* @scrutinizer ignore-unused */
    $response,
    /* @scrutinizer ignore-unused */
    $args
) {
    $subject      = (isset($args['subject'])) ? $args['subject'] : 'intro';
    $_server_info = $this->misc->getServerInfo();
    $query_string = $request->getUri()->getQuery();
    $server_id    = $request->getQueryParam('server');

    $this->utils->prtrace($_server_info);

    if (!isset($_server_info['username'])) {
        $subject = 'login';
    }

    if ($subject === 'login' && $server_id === null) {
        $subject = 'servers';
    }

    return $this->utils->maybeRenderIframes($response, $subject, $query_string);
});

$app->get('/', function (
    /* @scrutinizer ignore-unused */
    $request,
    /* @scrutinizer ignore-unused */
    $response,
    /* @scrutinizer ignore-unused */
    $args
) {
    $subject = 'intro';

    $query_string = $request->getUri()->getQuery();

    return $this->utils->maybeRenderIframes($response, $subject, $query_string);
});

$app->get('[/{path:.*}]', function ($request, $response, $args) {
    $filepath     = \BASE_PATH.'/'.$args['path'];
    $query_string = $request->getUri()->getQuery();

    $this->utils->dump($query_string, $filepath);

    //$this->utils->prtrace($request->getAttribute('route'));
    return $response->write($args['path'] ? $args['path'] : 'index');
});

// Run app
$app->run();
