<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class IntroController extends BaseController
{
    protected $no_db_connection = true;

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        if (null === $this->container->requestobj->getAttribute('route')) {
            echo $this->doDefault();
        } else {
            $body = $this->container->responseobj->getBody();
            $body->write($this->doDefault());

            return $this->container->responseobj;
        }
    }

    /**
     * Intro screen.
     *
     * Release: intro,v 1.19 2007/07/12 19:26:22 xzilla Exp $
     */
    public function doDefault()
    {
        $intro_html = $this->printHeader('Intro', $this->scripts, false);
        $intro_html .= $this->printBody(false);

        $intro_html .= $this->printTrail('root', false);

        $intro_html .= $this->printTabs('root', 'intro', false);

        $intro_html .= '<h1 style="margin-top:2em">' . $this->appName . ' ' . $this->appVersion . '</h1>';
        $intro_html .= '<h3>(PHP ' . PHP_VERSION . ')</h3>';

        $intro_html .= '<form method="get" action="intro">';
        $intro_html .= '<table>';
        $intro_html .= '<tr class="data1">';
        $intro_html .= '<th class="data">' . $this->lang['strlanguage'] . '</th>';
        $intro_html .= '<td>';
        $intro_html .= '<select name="language" onchange="this.form.submit()">';

        $language = isset($_SESSION['webdbLanguage']) ? $_SESSION['webdbLanguage'] : 'english';
        foreach ($this->appLangFiles as $k => $v) {
            $selected = ($k == $language) ? ' selected="selected"' : '';
            $intro_html .= "\t<option value=\"{$k}\"" . $selected . ">{$v}</option>" . PHP_EOL;
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
            $intro_html .= "\t<option value=\"{$k}\"" . $selected . ">{$v}</option>" . PHP_EOL;
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
