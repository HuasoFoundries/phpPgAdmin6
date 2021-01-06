<?php

/**
 * PHPPgAdmin 6.0.0
 */

use PHPPgAdmin\ContainerUtils;

foreach (['logs', 'sessions', 'twigcache'] as $tempFolder) {
    if (!\is_writable(\sprintf('%s/temp/%s', \dirname(__DIR__), $tempFolder))) {
        die(\sprintf('The folder temp/%s must be writable', $tempFolder));
    }
}

require_once \dirname(__DIR__) . '/vendor/autoload.php';
ini_set('display_errors','on');
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

$app->get('/status', function (
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    \phpinfo();

    return;
    //dump($this->get('settings')->all());
    return $response
        ->withHeader('Content-type', 'application/json')
        ->withJson(
            $this->DEBUGMODE ? $this->get('settings')->all() : ['version' => $this->version]
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
    /* @scrutinizer ignore-unused */
    \Slim\Http\Request $request,
    /* @scrutinizer ignore-unused */
    \Slim\Http\Response $response,
    /* @scrutinizer ignore-unused */
    array $args
) {
    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';
    $destinationurl = $this->getDestinationWithLastTab($subject);

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
        return $response->withStatus(302)->withHeader('Location',  $this->subFolder . '/src/views/servers');
    }

    if (!isset($_server_info['username']) && 'login' !== $subject && !$safe_subjects) {
        $destinationurl = $this->subFolder . '/src/views/login?server=' . $this->misc->getServerId();

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

$app->get('[/{path:.*}]', function (
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

    $this->prtrace($request->getAttribute('route'));

    return $response->write($args['path'] ? $args['path'] : 'index');
});

// Run app
$app->run();
