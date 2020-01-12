<?php

/**
 * PHPPgAdmin v6.0.0-RC1.
 */

namespace PHPPgAdmin\XHtml;

/**
 * Class to render tables. Formerly part of Misc.php.
 */
class HTMLFooterController extends HTMLController
{
    public $controller_name        = 'HTMLFooterController';
    private $_reload_drop_database = false;
    private $_no_bottom_link       = false;

    /**
     * Sets the value of $_reload_drop_database which in turn will trigger a reload in the browser frame.
     *
     * @param bool $flag sets internal $_reload_drop_database var which will be passed to the footer methods
     *
     * @return HTMLFooterController $this the instance of this class
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
     * @return HTMLFooterController $this the instance of this class
     */
    public function setNoBottomLink($flag)
    {
        $this->_no_bottom_link = (bool) $flag;

        return $this;
    }

    /**
     * fetches the page footer.
     *
     * @param string  $template    the template's name
     * @param array   $viewParams  Optional - extra view parameters
     *
     * @return string  the html content for the footer section
     */
    public function getFooter($template = 'footer.twig', $viewParams = [])
    {
        $reload_param = 'none';
        if ($this->misc->getReloadBrowser()) {
            $reload_param = 'other';
        } elseif ($this->_reload_drop_database) {
            $reload_param = 'database';
        }

        $this->view->offsetSet('script_footer', '');
//        $this->view->offsetSet('inPopUp', $inPopUp);
        $this->view->offsetSet('reload', $reload_param);
        $this->view->offsetSet('footer_template', $template);
        $this->view->offsetSet('print_bottom_link', !$this->_no_bottom_link);
        foreach ($viewParams as $key => $value) {
            $this->view->offsetSet($key, $value);
        }
        return $this->view->fetch($template);

    }

    /**
     * Outputs JavaScript to set default focus.
     *
     * @param string $object eg. forms[0].username
     */
    public function setFocus($object)
    {
        echo '<script type="text/javascript">' . PHP_EOL;
        echo "   document.{$object}.focus();\n";
        echo '</script>' . PHP_EOL;
    }

    /**
     * Outputs JavaScript to set the name of the browser window.
     *
     * @param string $name      the window name
     * @param bool   $addServer if true (default) then the server id is
     *                          attached to the name
     */
    public function setWindowName($name, $addServer = true)
    {
        echo '<script type="text/javascript">' . PHP_EOL;
        echo "//<![CDATA[\n";
        echo "   window.name = '{$name}", ($addServer ? ':' . htmlspecialchars($this->misc->getServerId()) : ''), "';\n";
        echo '//]]>' . PHP_EOL;
        echo '</script>' . PHP_EOL;
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
     * @param bool   $do_print if true, print html, if false, return html
     * @param stromg  $from     who is calling this method (mostly for debug)
     */
    public function printNavLinks($navlinks, $place, $env, $do_print, $from)
    {

        //$this->prtrace($navlinks);
        $plugin_manager = $this->plugin_manager;

        // Navlinks hook's place
        $plugin_functions_parameters = [
            'navlinks' => &$navlinks,
            'place'    => $place,
            'env'      => $env,
        ];
        $plugin_manager->doHook('navlinks', $plugin_functions_parameters);

        if (count($navlinks) > 0) {

            return $this->printLinksList($navlinks, 'navlink', $do_print, $from);

        }
    }
}
