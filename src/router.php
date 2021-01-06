<?php

/**
 * PHPPgAdmin 6.1.3
 */

foreach (['logs', 'sessions', 'twigcache'] as $tempFolder) {
    if (!\is_writable(\sprintf('%s/temp/%s', \dirname(__DIR__), $tempFolder))) {
        die(\sprintf('The folder temp/%s must be writable', $tempFolder));
    }
}

require_once \dirname(__DIR__) . '/vendor/autoload.php';

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__));

\defined('THEME_PATH') || \define('THEME_PATH', \dirname(__DIR__) . '/assets/themes');
// Enforce PHP environment
\ini_set('arg_separator.output', '&amp;');
\defined('ADODB_ERROR_HANDLER_TYPE') || \define('ADODB_ERROR_HANDLER_TYPE', \E_USER_ERROR);
\defined('ADODB_ERROR_HANDLER') || \define('ADODB_ERROR_HANDLER', '\PHPPgAdmin\ADOdbException::adodb_throw');

$shouldSetSession = (\defined('PHP_SESSION_ACTIVE') ? \PHP_SESSION_ACTIVE !== \session_status() : !\session_id())
    && !\headers_sent()
    && !\ini_get('session.auto_start');

if ($shouldSetSession && \PHP_SAPI !== 'cli') {
    \session_set_cookie_params(0, '/', $_SERVER['HTTP_HOST'], isset($_SERVER['HTTPS']));
    \session_name('PPA_ID');
    \session_start();
}

\defined('ADODB_ASSOC_CASE') || \define('ADODB_ASSOC_CASE', ADODB_ASSOC_CASE_NATIVE);

$app = getAppInstance();
$container = $app->getContainer();

// If no dump function has been globally declared at this point
// we fill the gap with an empty one to avoid errors 
if(!function_exists('dump')) {
    function dump(...$args):void {
        // do nothing
    }
}

// Set the requestobj and responseobj properties of the container
// as the value of $request and $response, which already contain the route
$app->add(new \PHPPgAdmin\Middleware\PopulateRequestResponse($container));

if (!isset($msg)) {
    $msg = '';
}
$container['msg'] = $msg;
//ddd($container->misc);

$app->get('/status', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withJson(
            $this->get('settings')['debug'] ? $this->get('settings')->all() : ['version' => $this->version]
        );
});

$app->post('/redirect/server', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
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
            //ddd($misc->getErrorMsg());
            $login_controller = new \PHPPgAdmin\Controller\LoginController($this, true);
            $body->write($login_controller->doLoginForm($misc->getErrorMsg()));

            return $response;
        }
        // Check for shared credentials
        if (null !== $loginShared) {
            $_SESSION['sharedUsername'] = $loginUsername;
            $_SESSION['sharedPassword'] = $loginPassword;
        }

        $this->view->setReloadBrowser(true);
        $this->addFlash(true, 'reload_browser');

        $destinationurl = $this->getDestinationWithLastTab('alldb');

        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }
    $_server_info = $this->misc->getServerInfo();

    if (!isset($_server_info['username'])) {
        $destinationurl = $this->getDestinationWithLastTab('server');

        return $response->withStatus(302)->withHeader('Location', $destinationurl);
    }
});

$app->get('/redirect[/{subject}]', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';
    $destinationurl = $this->getDestinationWithLastTab($subject);
    $cleanDestination=($this->subFolder.'/'. $destinationurl);
    
     return $response->withStatus(302)->withHeader('Location',$cleanDestination);
});

ini_set('display_errors','on');
$app->get('/{subject:servers|intro|browser}[/{server_id}]', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    $subject = $args['subject'] ?? 'intro';
    $this->view->offsetSet('includeJsTree',true);
    $className = '\PHPPgAdmin\Controller\\' . \ucfirst($subject) . 'Controller';
    $controller = new $className($this);
    return $controller->render();
 
    
});


$app->map(['GET', 'POST'], '/src/views/{subject}', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    $subject = $args['subject'];
    $nextPath=$this->subFolder.'/'. $subject;
    $query_string = $request->getUri()->getQuery();
return $response->withStatus(302)->withHeader('Location',$nextPath.($query_string? '?'.$query_string:'')); 
});

$app->get('/{subject:\w+}[/{server_id}]', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    $subject = $args['subject'] ?? 'intro';
    $server_id = $args['server_id'] ?? $request->getQueryParam('server');
    $_server_info = $this->misc->getServerInfo();
    if (!isset($_server_info['username'])) {
        $subject = 'login';
    }

    if ('login' === $subject && null === $server_id) {
        $subject = 'servers';
    }
    $query_string = $request->getUri()->getQuery();
    $this->view->offsetSet('includeJsTree',true);
    $className = $this->view->getControllerClassName($subject);
    $controller = new $className($this);
    return $controller->render();

});

$app->get('/', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    $subject = 'intro';
    $query_string = $request->getUri()->getQuery();
    return $response->withStatus(302)->withHeader('Location',$nextPath); 

    return $this->view->maybeRenderIframes($response, $subject, $query_string);
});

$app->get('[/{path:.*}]', function (
    \Slim\Http\Request $request,
    \Slim\Http\Response $response,
    array $args
) {
    return $response->write(sprintf("We couldn't find a route matching %s", $args['path'] ? $args['path'] : 'index'));
});

// Run app
$app->run();
