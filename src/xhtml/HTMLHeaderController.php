<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render tables. Formerly part of Misc.php.
 */
class HTMLHeaderController extends HTMLController
{
    public $controller_name = 'HTMLHeaderController';

    private $_no_output = false;

    private $_reload_drop_database = false;

    /**
     * Sets the value of private member variable $_no_output.
     *
     * @param bool $flag [description]
     *
     * @return $this
     */
    public function setNoOutput($flag)
    {
        $this->_no_output = (bool) $flag;

        return $this;
    }

    /**
     * Prints the page header.  If member variable $this->_no_output is
     * set then no header is drawn.
     *
     * @param string $title    The title of the page
     * @param string $script   script tag
     * @param bool   $do_print boolean if false, the function will return the header content
     * @param string $template the template to render
     *
     * @return string the parsed template
     */
    public function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig')
    {
        $lang = $this->lang;

        $viewVars = [];

        $viewVars['dir'] = (0 !== \strcasecmp($lang['applangdir'], 'ltr')) ? ' dir="' . \htmlspecialchars($lang['applangdir']) . '"' : '';
        $viewVars['headertemplate'] = $template;
        $viewVars['headerFlags'][str_replace('.twig', '', basename($template))] = 1;
        $viewVars['includeJsTree'] = true;
        //$viewVars['excludeJsTree']=$template==='header_sqledit.twig';
        $viewVars['title'] = ('' !== $title) ? ' - ' . $title : '';
        $viewVars['lang'] = $lang;
        $viewVars['appName'] = \htmlspecialchars($this->appName);

        $reload_param = 'none';
        if ($this->view->getReloadBrowser()) {
            $reload_param = 'other';
        } elseif ($this->_reload_drop_database) {
            $reload_param = 'database';
        }
        $viewVars['reload'] = $reload_param;
        $viewVars['script'] = $script;
        if (!$this->view->offsetExists('excludeJsTree')) {
            $this->view->offsetSet('excludeJsTree', false);
        }
        $template = $this->view->offsetGet('excludeJsTree') === true && $template === 'header_sqledit.twig' ? $template : 'header.twig';
        $header_html = $this->view->fetch($template, $viewVars);

        /*$plugins_head = [];
        $_params      = ['heads' => &$plugins_head];

        foreach ($plugins_head as $tag) {
        $header_html .= $tag;
        }*/

        if (!$this->_no_output && $do_print) {
            \header('Content-Type: text/html; charset=utf-8');
            echo $header_html;

            return '';
        }

        return $header_html;
    }

    /**
     * Prints the page body.
     *
     * @param bool   $doBody     True to output body tag, false to return
     * @param string $bodyClass  - name of body class
     * @param bool   $onloadInit - if true, call init() on body load event
     * @param bool   $includeJsTree - if true, include the jstree section
     *
     * @return string the parsed template
     */
    public function printBody(
        $doBody = true,
        $bodyClass = 'detailbody',
        $onloadInit = false,
        $includeJsTree = true
    ) {

        // $includeJsTree=$includeJsTree||(       $this->view->offsetExists('includeJsTree')?$this->view->offsetGet('includeJsTree'):false);
        $viewVars = [
            'bodyClass' => $this->lang['applangdir'] . ' ' . \htmlspecialchars($bodyClass) . ' ' . $includeJsTree ? 'flexbox_body' : '',
            'onload' => ($onloadInit ? 'onload="init();" ' : ''),
            'controller_name' => $this->controller_name,
            'includeJsTree' => $includeJsTree
        ];

        $bodyHtml = $this->view->fetch('components/common_body.twig', $viewVars);

        if (!$this->_no_output && $doBody) {
            echo $bodyHtml;

            return '';
        }

        return $bodyHtml;
    }

    /**
     * Print out the page heading and help link.
     *
     * @param string $title    Title, already escaped
     * @param string $help     (optional) The identifier for the help link
     * @param bool   $do_print
     *
     * @return string the parsed template
     */
    public function printTitle($title, $help = null, $do_print = true)
    {
        $title_html = '<h2>';
        $title_html .= $this->view->printHelp($title, $help, false);
        $title_html .= '</h2>' . \PHP_EOL;

        if ($do_print) {
            echo $title_html;

            return '';
        }

        return $title_html;
    }
}
