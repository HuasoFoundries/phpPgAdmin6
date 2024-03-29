<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render tables. Formerly part of Misc.php.
 */
class HTMLFooterController extends HTMLController
{
    public $controller_name = 'HTMLFooterController';

    private $_reload_drop_database = false;

    private $_no_bottom_link = false;

    /**
     * Sets the value of $_reload_drop_database which in turn will trigger a reload in the browser frame.
     *
     * @param bool $flag sets internal $_reload_drop_database var which will be passed to the footer methods
     *
     * @return static $this the instance of this class
     */
    public function setReloadDropDatabase($flag)
    {
        $this->_reload_drop_database = (bool) $flag;

        return $this;
    }

    /**
     * Sets $_no_bottom_link boolean value.
     *
     * @param bool $flag [description]
     *
     * @return static $this the instance of this class
     */
    public function setNoBottomLink($flag)
    {
        $this->_no_bottom_link = (bool) $flag;

        return $this;
    }

    /**
     * Prints the page footer.
     *
     * @param bool  $doBody   True to output body tag, false to return the html
     * @param mixed $template
     *
     * @return null|string
     */
    public function printFooter($doBody = true, $template = 'footer.twig')
    {
        $reload_param = 'none';

        if ($this->view->getReloadBrowser()) {
            $reload_param = 'other';
        } elseif ($this->_reload_drop_database) {
            $reload_param = 'database';
        }

        $this->view->offsetSet('script_footer', '');
        $this->view->offsetSet('reload', $reload_param);
        $this->view->offsetSet('footer_template', $template);
        $this->view->offsetSet('print_bottom_link', !$this->_no_bottom_link);

        if (!$this->view->offsetExists('excludeJsTree')) {
            $this->view->offsetSet('excludeJsTree', false);
        }
        $template = $this->view->offsetGet('excludeJsTree') === true && 'footer_sqledit.twig' === $template ? $template : 'footer.twig';
        $footer_html = $this->view->fetch($template);

        if ($doBody) {
            echo $footer_html;

            return '';
        }

        return $footer_html;
    }

    /**
     * Outputs JavaScript to set default focus.
     *
     * @param string $object eg. forms[0].username
     */
    public function setFocus($object): void
    {
        echo '<script type="text/javascript">' . \PHP_EOL;
        echo "   document.{$object}.focus();\n";
        echo '</script>' . \PHP_EOL;
    }

    /**
     * Outputs JavaScript to set the name of the browser window.
     *
     * @param string $name      the window name
     * @param bool   $addServer if true (default) then the server id is
     *                          attached to the name
     */
    public function setWindowName($name, $addServer = true): void
    {
        echo '<script type="text/javascript">' . \PHP_EOL;
        echo "//<![CDATA[\n";
        echo "   window.name = '{$name}", ($addServer ? ':' . \htmlspecialchars($this->misc->getServerId()) : ''), "';\n";
        echo '//]]>' . \PHP_EOL;
        echo '</script>' . \PHP_EOL;
    }

    /**
     * Display the navlinks, below the table results.
     *
     * @param array  $navlinks An array with the the attributes and values that will be shown.
     *                         See printLinksList for array format.
     * @param string $place    Place where the $navlinks are displayed. Like 'display-browse',
     *                         where 'display' is the file (display) and 'browse' is the action
     * @param array  $env      - Associative array of defined variables in the scope of the caller.
     *                         Allows to give some environnement details to plugins.
     *                         and 'browse' is the place inside that code (doBrowse).
     * @param mixed  $from     can either be null, false or the method calling this one
     *
     * @return null|string
     */
    public function printNavLinks($navlinks, $place, $env, $from)
    {
        if (null === $from || false === $from) {
            $from = __METHOD__;
        }

        // Navlinks hook's place
        $plugin_functions_parameters = [
            'navlinks' => &$navlinks,
            'place' => $place,
            'env' => $env,
        ];

        if (0 < \count($navlinks)) {
            kdump($navlinks);

            return $this->printLinksList($navlinks, 'navlink', $from);
        }

        return '';
    }

    /**
     * Display a list of links.
     *
     * @param array       $links An associative array of links to print. See printLink function for
     *                           the links array format.
     * @param string      $class an optional HTML class or list of classes seprated by a space
     *                           WARNING: This field is NOT escaped! No user should be able to inject something here, use with care
     * @param null|string $from  which method is calling this one
     *
     * @return null|string
     */
    protected function printLinksList($links, $class = '', $from = null)
    {
        if (null === $from || false === $from) {
            $from = __METHOD__;
        }
        $list_html = "<ul class=\"{$class}\">" . \PHP_EOL;

        foreach ($links as $link) {
            $list_html .= "\t<li>";
            $list_html .= \str_replace('.php', '', $this->printLink($link, false, $from));
            $list_html .= '</li>' . \PHP_EOL;
        }
        $list_html .= '</ul>' . \PHP_EOL;

        return $list_html;
    }
}
