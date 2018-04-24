<?php

/**
 * Single entrypoint of the app
 */
require_once __DIR__ . '/src/lib.inc.php';

// This section is made to be able to parse requests coming from PHP Builtin webserver
if (PHP_SAPI === 'cli-server') {
    require_once __DIR__ . '/src/cli.router.php';
}
$app->get('/status', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withJson(['version' => $this->version]);
});

$app->post('/redirect[/{subject}]', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $body    = $response->getBody();
    $misc    = $this->misc;
    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';

    $loginShared   = $request->getParsedBodyParam('loginShared');
    $loginServer   = $request->getParsedBodyParam('loginServer');
    $loginUsername = $request->getParsedBodyParam('loginUsername');
    $loginPassword = $request->getParsedBodyParam('loginPassword_' . md5($loginServer));

    // If login action is set, then set session variables
    if (boolval($loginServer) && boolval($loginUsername) && $loginPassword !== null) {
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

        //
        //return $response->withStatus(302)->withHeader('Location', $destinationurl);
    } else {
        $_server_info = $this->misc->getServerInfo();

        if (!isset($_server_info['username'])) {
            $destinationurl = $this->utils->getDestinationWithLastTab($subject);
            return $response->withStatus(302)->withHeader('Location', $destinationurl);
        }
    }
});

$app->get('/redirect[/{subject}]', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';
    $this->utils->prtrace('redirect', $subject);
    $destinationurl = $this->utils->getDestinationWithLastTab($subject);
    return $response->withStatus(302)->withHeader('Location', $destinationurl);
});

$app->get('/src/views/browser', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $controller = new \PHPPgAdmin\Controller\BrowserController($this, true);
    return $controller->render();
});

$app->get('/src/views/jstree', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {

    $controller = new \PHPPgAdmin\Controller\BrowserController($this, true);
    return $controller->render('jstree');
});

$app->get('/src/views/login', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $controller = new \PHPPgAdmin\Controller\LoginController($this, true);
    return $controller->render();
});

$app->get('/src/views/servers', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $controller = new \PHPPgAdmin\Controller\ServersController($this, true);
    return $controller->render();
});

$app->get('/src/views/intro', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $this->utils->prtrace('intro');
    $controller = new \PHPPgAdmin\Controller\IntroController($this, true);
    return $controller->render();
});

$app->map(['GET', 'POST'], '/src/views/{subject}', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    if ($this->misc->getServerId() === null) {
        return $response->withStatus(302)->withHeader('Location', SUBFOLDER . '/src/views/servers');
    }
    $_server_info = $this->misc->getServerInfo();

    if (!isset($_server_info['username'])) {
        $destinationurl = SUBFOLDER . '/src/views/login?server=' . $this->misc->getServerId();
        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }

    $subject = $args['subject'];

    $className  = '\PHPPgAdmin\Controller\\' . ucfirst($subject) . 'Controller';
    $controller = new $className($this);
    return $controller->render();
});

function maybeRenderIframes($c, $response, $subject, $query_string)
{
    $in_test = $c->view->offsetGet('in_test');

    if ($in_test === '1') {
        $className  = '\PHPPgAdmin\Controller\\' . ucfirst($subject) . 'Controller';
        $controller = new $className($c);
        return $controller->render();
    }

    $viewVars = [
        'url'            => '/src/views/' . $subject . ($query_string ? '?' . $query_string : ''),
        'headertemplate' => 'header.twig',
    ];

    return $c->view->render($response, 'iframe_view.twig', $viewVars);
};

$app->get('/{subject:\w+}', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $subject      = (isset($args['subject'])) ? $args['subject'] : 'intro';
    $_server_info = $this->misc->getServerInfo();
    $query_string = $request->getUri()->getQuery();
    $server_id    = $request->getQueryParam('server');

    //$this->utils->dumpAndDie($_server_info);

    if (!isset($_server_info['username'])) {
        $subject = 'login';
    }

    if ($subject === 'login' && $server_id === null) {
        $subject = 'servers';
    }

    return maybeRenderIframes($this, $response, $subject, $query_string);

});

$app->get('/', function (
    /** @scrutinizer ignore-unused */$request,
    /** @scrutinizer ignore-unused */$response,
    /** @scrutinizer ignore-unused */$args
) {
    $subject = 'intro';
    //$this->utils->dumpAndDie(\SUBFOLDER);
    $query_string = $request->getUri()->getQuery();

    return maybeRenderIframes($this, $response, $subject, $query_string);
});

$app->get('[/{path:.*}]', function ($request, $response, $args) {
    $filepath     = \BASE_PATH . '/' . $args['path'];
    $query_string = $request->getUri()->getQuery();

    $this->utils->dump($query_string);
    $this->utils->dump($filepath);

    //$this->utils->prtrace($request->getAttribute('route'));
    return $response->write($args['path'] ? $args['path'] : 'index');
});

// Run app
$app->run();
