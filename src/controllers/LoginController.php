<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\Controller;

/**
 * Login controller class.
 */
class LoginController extends BaseController
{
    protected $container;
    protected $_connection;
    protected $app;
    protected $data;
    protected $database;
    protected $server_id;
    public $appLangFiles = [];
    public $appThemes = [];
    public $appName = '';
    public $appVersion = '';
    public $form = '';
    public $href = '';
    public $lang = [];
    public $action = '';
    public $controller_name = 'LoginController';
    public $controller_title = 'strlogin';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if (null === $this->container->requestobj->getAttribute('route')) {
            echo $this->doLoginForm();
        } else {
            $body = $this->container->responseobj->getBody();
            $body->write($this->doLoginForm());

            return $this->container->responseobj;
        }
    }

    public function doLoginForm($msg = '')
    {
        $lang = $this->lang;

        $this->misc->setNoDBConnection(true);

        $server_id = $this->container->requestobj->getQueryParam('server');

        if (null === $server_id) {
            $this->prtrace('invalid server param');

            return $this->lang['strinvalidserverparam'];
        }

        $login_html = $this->printHeader($lang[$this->controller_title], $this->scripts, false);
        $login_html .= $this->printBody(false);
        $login_html .= $this->printTrail('root', false);

        if (!empty($_POST)) {
            $vars = &$_POST;
        } else {
            $vars = &$_GET;
        }
        foreach ($_REQUEST as $key => $val) {
            if (false !== strpos($key, '?')) {
                $namexploded = explode('?', $key);
                $_REQUEST[$namexploded[1]] = htmlspecialchars($val);
            }
        }

        $server_info = $this->misc->getServerInfo($server_id);
        $title = sprintf($lang['strlogintitle'], $server_info['desc']);

        $printTitle = $this->printTitle($title, null, false);

        $login_html .= $printTitle;

        if (isset($msg)) {
            $login_html .= $this->printMsg($msg, false);
        }

        $login_html .= '<form id="login_form"  method="post" name="login_form" action="'.\SUBFOLDER.'/redirect/server?server='.htmlspecialchars($server_id).'">';

        $md5_server = md5($server_id);
        // Pass request vars through form (is this a security risk???)
        foreach ($vars as $key => $val) {
            if ('login' == substr($key, 0, 5)) {
                continue;
            }
            if (false !== strpos($key, '?')) {
                $key = explode('?', $key)[1];
            }

            $login_html .= '<input type="hidden" name="'.htmlspecialchars($key).'" value="'.htmlspecialchars($val).'" />'."\n";
        }

        $login_html .= '<input type="hidden" name="loginServer" value="'.htmlspecialchars($server_id).'" />';
        $login_html .= '<table class="navbar" border="0" cellpadding="5" cellspacing="3">';
        $login_html .= '<tr>';
        $login_html .= '<td>'.$lang['strusername'].'</td>';
        $loginusername = isset($_POST['loginUsername']) ? htmlspecialchars($_POST['loginUsername']) : '';

        $login_html .= '<td><input type="text" name="loginUsername" value="'.$loginusername.'" size="24" /></td>';
        $login_html .= '</tr>';
        $login_html .= '<tr>';
        $login_html .= '<td>'.$lang['strpassword'].'</td>';
        $login_html .= '<td><input id="loginPassword" type="password" name="loginPassword_'.$md5_server.'" size="24" /></td>';
        $login_html .= '</tr>';
        $login_html .= '</table>';
        if (sizeof($this->conf['servers']) > 1) {
            $checked = isset($_POST['loginShared']) ? 'checked="checked"' : '';
            $login_html .= '<p><input type="checkbox" id="loginShared" name="loginShared" '.$checked.' />';
            $login_html .= '<label for="loginShared">'.$lang['strtrycred'].'</label></p>';
        }
        $login_html .= '<p><input type="submit" name="loginSubmit" value="'.$lang['strlogin'].'" /></p>';
        $login_html .= '</form>';

        $login_html .= '<script type="text/javascript">';
        $login_html .= '	var uname = document.login_form.loginUsername;';
        $login_html .= '	var pword = document.login_form.loginPassword_'.$md5_server.';';
        $login_html .= '	if (uname.value == "") {';
        $login_html .= '		uname.focus();';
        $login_html .= '	} else {';
        $login_html .= '		pword.focus();';
        $login_html .= '	}';
        $login_html .= '</script>';

        // Output footer
        $login_html .= $this->printFooter(false);

        return $login_html;
    }
}
