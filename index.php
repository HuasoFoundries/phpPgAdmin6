<?php

/**
 * Single entrypoint of the app
 */
require_once './src/lib.inc.php';

$app->post('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

    $body         = $response->getBody();
    $query_string = $request->getUri()->getQuery();
    $misc         = $this->misc;

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
        $all_db_controller = new \PHPPgAdmin\Controller\AllDBController($this);
        return $all_db_controller->render();

    } else {

        $_server_info = $this->misc->getServerInfo();

        if (!isset($_server_info['username'])) {
            $destinationurl = $this->utils->getDestinationWithLastTab($subject);
            return $response->withStatus(302)->withHeader('Location', $destinationurl);
        }
    }

});

$app->get('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

    $subject        = (isset($args['subject'])) ? $args['subject'] : 'root';
    $destinationurl = $this->utils->getDestinationWithLastTab($subject);
    return $response->withStatus(302)->withHeader('Location', $destinationurl);

});

$app->get('/src/views/{subject:[intro|login|servers|browser]+}', function ($request, $response, $args) use ($msg) {

    $subject    = $args['subject'];
    $className  = '\PHPPgAdmin\Controller\\' . ucfirst($subject) . 'Controller';
    $controller = new $className($this, true);
    return $controller->render();
});

$app->map(['GET', 'POST'], '/src/views/{subject}', function ($request, $response, $args) use ($msg) {

    $subject    = $args['subject'];
    $className  = '\PHPPgAdmin\Controller\\' . ucfirst($subject) . 'Controller';
    $controller = new $className($this);
    return $controller->render();
});

$app->get('/[{subject}]', function ($request, $response, $args) use ($msg) {

    $subject      = (isset($args['subject'])) ? $args['subject'] : 'intro';
    $_server_info = $this->misc->getServerInfo();
    $query_string = $request->getUri()->getQuery();
    $server_id    = $request->getQueryParam('server');

    if (!isset($_server_info['username']) && ($subject === 'server' || $subject === 'root')) {
        $subject = 'login';
    }

    if ($subject === 'login' && $server_id === null) {
        $subject = 'servers';
    }

    $viewVars = [
        'url'            => '/src/views/' . $subject . ($query_string ? '?' . $query_string : ''),
        'headertemplate' => 'header.twig',
    ];

    return $this->view->render($response, 'iframe_view.twig', $viewVars);
});

// Run app
$app->run();
