<?php

namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class IntroController extends BaseController
{
    public $_name = 'IntroController';

    public function render()
    {

        if ($this->container->requestobj->getAttribute('route') === null) {
            echo $this->doDefault();
        } else {
            $body = $this->container->responseobj->getBody();
            $body->write($this->doDefault());
            return $this->container->responseobj;
        }

    }

    /**
     * Intro screen
     *
     * $Id: intro.php,v 1.19 2007/07/12 19:26:22 xzilla Exp $
     */
    public function doDefault()
    {

        $intro_html = $this->printHeader('Intro', $this->scripts, false);
        $intro_html .= $this->printBody(false);

        $intro_html .= $this->printTrail('root', false);

        $intro_html .= $this->printTabs('root', 'intro', false);

        $intro_html .= '<h1>' . $this->appName . ' ' . $this->appVersion . ' (PHP ' . PHP_VERSION . ')</h1>';

        $intro_html .= '<form method="get" action="intro.php">';
        $intro_html .= '<table>';
        $intro_html .= '<tr class="data1">';
        $intro_html .= '<th class="data">' . $this->lang['strlanguage'] . '</th>';
        $intro_html .= '<td>';
        $intro_html .= '<select name="language" onchange="this.form.submit()">';

        $this->language = isset($_SESSION['webdbLanguage']) ? $_SESSION['webdbLanguage'] : 'english';
        foreach ($this->appLangFiles as $k => $v) {
            $selected = ($k == $this->language) ? ' selected="selected"' : '';
            $intro_html .= "\t<option value=\"{$k}\"" . $selected . ">{$v}</option>\n";
        }

        $intro_html .= '</select>';
        $intro_html .= '</td>';
        $intro_html .= '</tr>';
        $intro_html .= '<tr class="data2">';
        $intro_html .= '<th class="data">' . $this->lang['strtheme'] . '</th>';
        $intro_html .= '<td>';
        $intro_html .= '<select name="theme" onchange="this.form.submit()">';

        foreach ($this->appThemes as $k => $v) {
            $selected = ($k == $this->conf['theme']) ? ' selected="selected"' : '';
            $intro_html .= "\t<option value=\"{$k}\"" . $selected . ">{$v}</option>\n";
        }

        $intro_html .= '</select>';
        $intro_html .= '</td>';
        $intro_html .= '</tr>';
        $intro_html .= '</table>';
        $intro_html .= '<noscript><p><input type="submit" value="' . $this->lang['stralter'] . '" /></p></noscript>';
        $intro_html .= '</form>';

        $intro_html .= '<p>' . $this->lang['strintro'] . '</p>';

        $intro_html .= '<ul class="intro">';
        $intro_html .= '	<li><a href="https://github.com/HuasoFoundries/phpPgAdmin6">' . $this->lang['strppahome'] . '</a></li>';
        $intro_html .= '<li><a href="' . $this->lang['strpgsqlhome_url'] . '">' . $this->lang['strpgsqlhome'] . '</a></li>';
        $intro_html .= '<li><a href="https://github.com/HuasoFoundries/phpPgAdmin6/issues">' . $this->lang['strreportbug'] . '</a></li>';
        //$intro_html .= '<li><a href="' . $this->lang['strviewfaq_url'] . '">' . $this->lang['strviewfaq'] . '</a></li>';
        $intro_html .= '</ul>';

        if ($this->container->requestobj->getQueryParam('language')) {
            $this->misc->setReloadBrowser(true);
        }

        $intro_html .= $this->printFooter(false);

        return $intro_html;
    }

}
