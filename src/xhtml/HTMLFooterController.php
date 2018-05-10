<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
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
     * Prints the page footer.
     *
     * @param bool  $doBody   True to output body tag, false to return the html
     * @param mixed $template
     */
    public function printFooter($doBody = true, $template = 'footer.twig')
    {
        $lang = $this->lang;

        $reload_param = 'none';
        if ($this->misc->getReloadBrowser()) {
            $reload_param = 'other';
        } elseif ($this->_reload_drop_database) {
            $reload_param = 'database';
        }

        $this->view->offsetSet('script_footer', '');
        $this->view->offsetSet('reload', $reload_param);
        $this->view->offsetSet('footer_template', $template);
        $this->view->offsetSet('print_bottom_link', !$this->_no_bottom_link);

        $footer_html = $this->view->fetch($template);

        if ($doBody) {
            echo $footer_html;
        } else {
            return $footer_html;
        }
    }

    /**
     * Outputs JavaScript to set default focus.
     *
     * @param string $object eg. forms[0].username
     */
    public function setFocus($object)
    {
        echo "<script type=\"text/javascript\">\n";
        echo "   document.{$object}.focus();\n";
        echo "</script>\n";
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
        echo "<script type=\"text/javascript\">\n";
        echo "//<![CDATA[\n";
        echo "   window.name = '{$name}", ($addServer ? ':' . htmlspecialchars($this->misc->getServerId()) : ''), "';\n";
        echo "//]]>\n";
        echo "</script>\n";
    }
}
