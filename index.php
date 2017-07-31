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

    $pwdkey = 'loginPassword_' . md5($_POST['loginServer']);
    // If login action is set, then set session variables
    if (isset($_POST['loginServer']) && isset($_POST['loginUsername']) &&
        isset($_POST['loginPassword_' . md5($_POST['loginServer'])])) {

        $_server_info = $this->misc->getServerInfo($_POST['loginServer']);

        $_server_info['username'] = $_POST['loginUsername'];
        $_server_info['password'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];

        $this->misc->setServerInfo(null, $_server_info, $_POST['loginServer']);

        // Check for shared credentials
        if (isset($_POST['loginShared'])) {
            $_SESSION['sharedUsername'] = $_POST['loginUsername'];
            $_SESSION['sharedPassword'] = $_POST['loginPassword_' . md5($_POST['loginServer'])];
        }

        $data = $misc->getDatabaseAccessor();

        $all_db_controller = new \PHPPgAdmin\Controller\AllDBController($this);

        $misc->printHeader($this->lang['strdatabases']);
        $misc->printBody();

        $all_db_controller->doDefault();

        $misc->setReloadBrowser(true);
        $misc->printFooter(true);

        //$body->write($this->misc->printFooter(false));

    } else {
        $_server_info = $this->misc->getServerInfo();

        if (!isset($_server_info['username'])) {

            $login_controller = new \PHPPgAdmin\Controller\LoginController($this);
            $body->write($login_controller->doLoginForm($msg));

        }
    }

    return $response;

});

//function renderTemplate($container,$request, )

$app->get('/', function ($request, $response, $args) use ($msg) {

    $uri = $request->getUri();

    $base_and_qs = explode('?', $uri->getQuery());
    //\PC::debug($base_and_qs, 'base_and_qs on route /');

    $query_string = '';
    if (count($base_and_qs) >= 2) {
        $query_string = '?' . $base_and_qs[1];
    }

    $viewVars = $this->lang;

    $viewVars['appName'] = $this->get('settings')['appName'];
    $subject             = 'intro';
    $viewVars['rtl']     = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

    if ($viewVars['rtl']) {
        $viewVars['cols'] = '*,' . $this->conf['left_width'];
        $template         = 'iframe_view_rtl.twig';
    } else {
        $viewVars['cols'] = $this->conf['left_width'] . ',*';
        $template         = 'iframe_view.twig';
    }
    $viewVars['headertemplate'] = 'iframe_header.twig';
    $url                        = '/src/views/' . $subject . '.php' . $query_string;
    $viewVars['url']            = $url;

    return $this->view->render($response, $template, $viewVars);

})->setName('home');

$app->get('/redirect[/{subject}]', function ($request, $response, $args) use ($msg, $container) {

    $subject = (isset($args['subject'])) ? $args['subject'] : 'root';

    $_server_info = $this->misc->getServerInfo();

    $body = $response->getBody();

    \PC::debug('subject is ' . $subject);

    // If username isn't set in server_info, you should login
    if (!isset($_server_info['username'])) {

        $server_id = $request->getQueryParam('server');

        // but if server_id isn't set, then you will be redirected to intro
        if ($server_id === null) {

            return $response->withStatus(302)->withHeader('Location', SUBFOLDER . '/src/views/intro.php');

        } else {

            $this->misc->setNoDBConnection(true);

            $controller = new \PHPPgAdmin\Controller\LoginController($this);
            $body_html  = $controller->doLoginForm($msg);
        }
        $body->write($body_html);

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

$app->get('/{subject}', function ($request, $response, $args) use ($msg, $container) {
    $subject = (isset($args['subject'])) ? $args['subject'] : 'intro';
    if ($subject === 'server' || $subject === 'root') {
        $subject = 'login';
    }

    \PC::debug($subject, 'subject on route /{subject}');

    $uri         = $request->getUri();
    $base_and_qs = explode('?', $uri->getQuery());

    $query_string = '';
    if (count($base_and_qs) >= 2) {
        $query_string = '?' . $base_and_qs[1];
    }

    $url = '/src/views/' . $subject . '.php' . $query_string;

    \PC::debug(['subject' => $subject, 'url' => $url], 'subject');
    $viewVars['rtl'] = (strcasecmp($this->lang['applangdir'], 'rtl') == 0);

    if ($viewVars['rtl']) {
        $viewVars['cols'] = '*,' . $this->conf['left_width'];
        $template         = 'iframe_view_rtl.twig';
    } else {
        $viewVars['cols'] = $this->conf['left_width'] . ',*';
        $template         = 'iframe_view.twig';
    }

    $viewVars            = $this->lang;
    $viewVars['appName'] = $this->get('settings')['appName'];
    $viewVars['url']     = $url;

    return $this->view->render($response, $template, $viewVars);
})->setName('subject');

// Run app
$app->run();
