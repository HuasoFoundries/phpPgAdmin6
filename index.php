<?php

/**
 * Main access point to the app.
 *
 * $Id: index.php,v 1.13 2007/04/18 14:08:48 mr-russ Exp $
 */

// Include application functions

require_once './src/lib.inc.php';

/*$app->post('/info', function ($request, $response, $args) use ($msg) {

var_dump($_SERVER);

return $response;
});*/

$app->post('/redirect[/{subject}]', function ($request, $response, $args) use ($msg) {

    $body = $response->getBody();
    $misc = $this->misc;

    $loginShared   = $request->getParsedBodyParam('loginShared');
    $loginServer   = $request->getParsedBodyParam('loginServer');
    $loginUsername = $request->getParsedBodyParam('loginUsername');
    $loginPassword = $request->getParsedBodyParam('loginPassword_' . md5($loginServer));

    // If login action is set, then set session variables
    if ($loginServer !== null && $loginUsername !== null && $loginPassword !== null) {

        $_server_info = $this->misc->getServerInfo($loginServer);

        $_server_info['username'] = $loginUsername;
        $_server_info['password'] = $loginPassword;

        $this->misc->setServerInfo(null, $_server_info, $loginServer);

        // Check for shared credentials
        if ($loginShared !== null) {
            $_SESSION['sharedUsername'] = $loginUsername;
            $_SESSION['sharedPassword'] = $loginPassword;
        }

        $data = $misc->getDatabaseAccessor();

        $all_db_controller = new \PHPPgAdmin\Controller\AllDBController($this);

        $all_db_controller->printHeader($this->lang['strdatabases']);
        $all_db_controller->printBody();

        $all_db_controller->doDefault();

        $misc->setReloadBrowser(true);
        $all_db_controller->printFooter(true);

        //$body->write($this->misc->printFooter(false));

    } else {
        $_server_info = $this->misc->getServerInfo();

        if (!isset($_server_info['username'])) {

            $server_id = $request->getQueryParam('server');

            // but if server_id isn't set, then you will be redirected to intro
            if ($server_id === null) {

                return $response->withStatus(302)->withHeader('Location', SUBFOLDER . '/src/views/intro.php');

            } else {

                $this->misc->setNoDBConnection(true);

                $controller = new \PHPPgAdmin\Controller\LoginController($this, true);
                $body_html  = $controller->doLoginForm($msg);
                $body->write($body_html);
            }

        }
    }

    return $response;

});

$app->get('/redirect[/{subject}]', function ($request, $response, $args) use ($msg, $container) {

    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';

    $_server_info = $this->misc->getServerInfo();

    $body = $response->getBody();

    // If username isn't set in server_info, you should login
    if (!isset($_server_info['username'])) {

        $server_id = $request->getQueryParam('server');

        // but if server_id isn't set, then you will be redirected to intro
        if ($server_id === null) {

            return $response->withStatus(302)->withHeader('Location', SUBFOLDER . '/src/views/intro.php');

        } else {

            $this->misc->setNoDBConnection(true);

            $controller = new \PHPPgAdmin\Controller\LoginController($this, true);
            $body_html  = $controller->doLoginForm($msg);
            $body->write($body_html);
        }

        return $response;

    } else {

        $url = $this->misc->getLastTabURL($subject);

        $include_file = $url['url'];

        // Load query vars into superglobal arrays
        if (isset($url['urlvars'])) {
            $urlvars = [];

            foreach ($url['urlvars'] as $key => $urlvar) {
                if (strpos($key, '?') !== false) {
                    $key = explode('?', $key)[1];
                }
                $urlvars[$key] = \PHPPgAdmin\Decorators\Decorator::get_sanitized_value($urlvar, $_REQUEST);
            }

            $_REQUEST = array_merge($_REQUEST, $urlvars);
            $_GET     = array_merge($_GET, $urlvars);
        }

        $actionurl = \PHPPgAdmin\Decorators\Decorator::actionurl($include_file, $_GET);

        $destinationurl = str_replace("%2Fredirect%2F{$subject}%3F", '', $actionurl->value($_GET));

        return $response->withStatus(302)->withHeader('Location', $destinationurl);

    }
});

$app->get('/[{subject}]', function ($request, $response, $args) use ($msg, $container) {

    $subject = (isset($args['subject'])) ? $args['subject'] : 'intro';

    $_server_info = $this->misc->getServerInfo();

    if (!isset($_server_info['username']) && ($subject === 'server' || $subject === 'root')) {
        $subject = 'login';
    }

    //\PC::debug($subject, 'subject on route /{subject}');
    //die('subject on route /{subject} is ' . $subject);

    $uri  = $request->getUri();
    $path = $uri->getPath();

    $query_string = $uri->getQuery();

    $server_id = $request->getQueryParam('server');
    if ($subject === 'login' && $server_id === null) {
        $subject = 'servers';
    }

    $url = '/src/views/' . $subject . '.php' . ($query_string ? '?' . $query_string : '');

    $rtl = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

    $viewVars = [
        'path'           => $path,
        'url'            => $url,
        'headertemplate' => 'header.twig',
    ];

    if ($rtl) {
        $template = 'iframe_view_rtl.twig';
    } else {
        $template = 'iframe_view.twig';
    }

    return $this->view->render($response, $template, $viewVars);
})->setName('subject');

// Run app
$app->run();
