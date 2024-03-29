<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class IntroController extends BaseController
{
    protected $no_db_connection = true;

    /**
     * Default method to render the controller according to the action parameter.
     *
     * @return \Slim\Http\Response
     */
    public function render()
    {
        $body = \responseInstance()->getBody();
        $body->write($this->doDefault());

        return \responseInstance();
    }

    /**
     * Intro screen.
     *
     * Release: intro,v 1.19 2007/07/12 19:26:22 xzilla Exp $
     *
     * @return string
     */
    public function doDefault()
    {
        $intro_html = $this->printHeader('Intro', $this->scripts, false);
        $intro_html .= $this->printBody(false, 'flexbox_body', false, true);

        $intro_html .= $this->printTrail('root', false);

        $intro_html .= $this->printTabs('root', 'intro', false);

        $intro_html .= '<h1 style="margin-top:2em">' . $this->appName . ' ' . $this->appVersion . '</h1>';
        $intro_html .= '<h3>(PHP ' . \PHP_VERSION . ')</h3>';

        $intro_html .= '<form method="get" action="intro">';
        $intro_html .= '<table>';
        $intro_html .= '<tr class="data1">';
        $intro_html .= '<th class="data">' . $this->lang['strlanguage'] . '</th>';
        $intro_html .= '<td>';
        $intro_html .= '<select name="language" onchange="this.form.submit()">';

        $language = $_SESSION['webdbLanguage'] ?? 'english';

        foreach ($this->appLangFiles as $k => $v) {
            $selected = ($k === $language) ? ' selected="selected"' : '';
            $intro_html .= \sprintf(
                '	<option value="%s"',
                $k
            ) . $selected . \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
        }

        $intro_html .= '</select>';
        $intro_html .= '</td>';
        $intro_html .= '</tr>';
        $intro_html .= '<tr class="data2">';
        $intro_html .= '<th class="data">' . $this->lang['strtheme'] . '</th>';
        $intro_html .= '<td>';
        $intro_html .= '<select name="theme" onchange="this.form.submit()">';

        foreach ($this->appThemes as $k => $v) {
            $selected = ($k === $this->conf['theme']) ? ' selected="selected"' : '';
            $intro_html .= \sprintf(
                '	<option value="%s"',
                $k
            ) . $selected . \sprintf(
                '>%s</option>',
                $v
            ) . \PHP_EOL;
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

        if (\requestInstance()->getQueryParam('language')) {
            $this->view->setReloadBrowser(true);
        }

        return $intro_html . $this->printFooter(false);
    }
}
