<?php

/**
 * PHPPgAdmin 6.0.0
 */

require_once __DIR__ . '/lib.inc.php';
$app->get('/status', function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    //dump($this->get('settings')->all());
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withJson(
            DEBUGMODE ? $this->get('settings')->all() : ['version' => $this->version]
        );
});

$app->post('/redirect/server', function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    $body = $response->getBody();
    $misc = $this->misc;

    $loginShared = $request->getParsedBodyParam('loginShared');
    $loginServer = $request->getParsedBodyParam('loginServer');
    $loginUsername = $request->getParsedBodyParam('loginUsername');
    $loginPassword = $request->getParsedBodyParam('loginPassword_' . \md5($loginServer));

    // If login action is set, then set session variables
    if ((bool) $loginServer && (bool) $loginUsername && null !== $loginPassword) {
        $_server_info = $this->misc->getServerInfo($loginServer);

        $_server_info['username'] = $loginUsername;
        $_server_info['password'] = $loginPassword;

        $this->misc->setServerInfo(null, $_server_info, $loginServer);

        $data = $misc->getDatabaseAccessor();

        if (null === $data) {
            $login_controller = new \PHPPgAdmin\Controller\LoginController($this, true);
            $body->write($login_controller->doLoginForm($misc->getErrorMsg()));

            return $response;
        }
        // Check for shared credentials
        if (null !== $loginShared) {
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
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';
    $destinationurl = $this->utils->getDestinationWithLastTab($subject);

    return $response->withStatus(302)->withHeader('Location', $destinationurl);
});

$app->map(['GET', 'POST'], '/src/views/{subject}', function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    $subject = $args['subject'];

    if ('server' === $subject) {
        $subject = 'servers';
    }
    $_server_info = $this->misc->getServerInfo();

    $safe_subjects = ('servers' === $subject || 'intro' === $subject || 'browser' === $subject);

    if (null === $this->misc->getServerId() && !$safe_subjects) {
        return $response->withStatus(302)->withHeader('Location', SUBFOLDER . '/src/views/servers');
    }

    if (!isset($_server_info['username']) && 'login' !== $subject && !$safe_subjects) {
        $destinationurl = SUBFOLDER . '/src/views/login?server=' . $this->misc->getServerId();

        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }

    $className = '\PHPPgAdmin\Controller\\' . \ucfirst($subject) . 'Controller';
    $controller = new $className($this);

    return $controller->render();
});

$app->get('/{subject:\w+}[/{server_id}]', function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    $subject,
    $server_id = null
) {
    $subject = (isset($args['subject'])) ? $args['subject'] : 'intro';
    //ddd($subject, $server_id);
    $_server_info = $this->misc->getServerInfo();

    $server_id = $request->getQueryParam('server');

    //$this->utils->prtrace($_server_info);

    if (!isset($_server_info['username'])) {
        $subject = 'login';
    }

    if ('login' === $subject && null === $server_id) {
        $subject = 'servers';
    }
    $query_string = $request->getUri()->getQuery();

    return $this->view->maybeRenderIframes($response, $subject, $query_string);
});

$app->get('/', function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    $subject = 'intro';

    $query_string = $request->getUri()->getQuery();

    return $this->view->maybeRenderIframes($response, $subject, $query_string);
});

$app->get('[/{path:.*}]', static function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    $filepath = \dirname(__DIR__) . '/' . $args['path'];
    $query_string = $request->getUri()->getQuery();

    //d($this->subfolder, $args, $query_string, $filepath);

    //$this->utils->prtrace($request->getAttribute('route'));
    return $response->write($args['path'] ? $args['path'] : 'index');
});

// Run app
$app->run();
