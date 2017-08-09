<?php
namespace PHPPgAdmin;

trait HelperTrait
{
    /**
     * Receives N parameters and sends them to the console adding where was it called from
     * @return [type] [description]
     */
    public function prtrace()
    {

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = ([
            /*'class0'    => $backtrace[3]['class'],
            'type0'     => $backtrace[3]['type'],
            'function0' => $backtrace[3]['function'],
            'spacer0'   => ' ',
            'line0'     => $backtrace[2]['line'],

            'spacer1'   => ' ',

            'class1'    => $backtrace[2]['class'],
            'type1'     => $backtrace[2]['type'],
            'function1' => $backtrace[2]['function'],
            'spacer2'   => ' ',
            'line1'     => $backtrace[1]['line'],

            'spacer3'   => ' ',*/

            'class2'    => $backtrace[1]['class'],
            'type2'     => $backtrace[1]['type'],
            'function2' => $backtrace[1]['function'],
            'spacer4'   => ' ',
            'line2'     => $backtrace[0]['line'],
        ]);

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);
    }

    public static function statictrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = ([
            'class'    => $backtrace[1]['class'],
            'type'     => $backtrace[1]['type'],
            'function' => $backtrace[1]['function'],
            'spacer'   => ' ',
            'line'     => $backtrace[0]['line'],
        ]);

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);
    }

    /**
     * Returns a string with html <br> variant replaced with a new line
     * @param  string $msg [description]
     * @return string      [description]
     */
    public static function br2ln(string $msg)
    {
        return str_replace(['<br>', '<br/>', '<br />'], "\n", $msg);
    }

    /**
     * Prints the page header.  If member variable $this->_no_output is
     * set then no header is drawn.
     * @param $title The title of the page
     * @param $script script tag
     * @param $do_print boolean if false, the function will return the header content
     */
    public function printHeader($title = '', $script = null, $do_print = true, $template = 'header.twig')
    {

        if (function_exists('newrelic_disable_autorum')) {
            newrelic_disable_autorum();
        }
        $appName        = $this->appName;
        $lang           = $this->lang;
        $plugin_manager = $this->plugin_manager;

        //$this->prtrace('appName', $appName);

        $viewVars = [];
        //$viewVars = $this->lang;
        if (isset($_SESSION['isolang'])) {
            $viewVars['isolang']   = $_SESSION['isolang'];
            $viewVars['applocale'] = $lang['applocale'];
        }

        $viewVars['dir']            = (strcasecmp($lang['applangdir'], 'ltr') != 0) ? ' dir="' . htmlspecialchars($lang['applangdir']) . '"' : '';
        $viewVars['headertemplate'] = $template;
        $viewVars['title']          = $title;
        $viewVars['appName']        = htmlspecialchars($this->appName) . (($title != '') ? htmlspecialchars(" - {$title}") : '');

        //$this->prtrace($viewVars);
        $header_html = $this->view->fetch($template, $viewVars);

        if ($script) {
            $header_html .= "{$script}\n";
        }

        $plugins_head = [];
        $_params      = ['heads' => &$plugins_head];

        $plugin_manager->do_hook('head', $_params);

        foreach ($plugins_head as $tag) {
            $header_html .= $tag;
        }

        $header_html .= "</head>\n";

        if (!$this->_no_output && $do_print) {

            header('Content-Type: text/html; charset=utf-8');
            echo $header_html;

        } else {
            return $header_html;
        }
    }

    /**
     * Prints the page body.
     * @param $doBody True to output body tag, false to return
     * @param $bodyClass - name of body class
     */
    public function printBody($doBody = true, $bodyClass = 'detailbody')
    {

        $bodyClass = htmlspecialchars($bodyClass);
        $bodyHtml  = '<body data-controller="' . $this->_name . '" class="' . $bodyClass . '" >';
        $bodyHtml .= "\n";

        if (!$this->_no_output && $doBody) {
            echo $bodyHtml;
        } else {
            return $bodyHtml;
        }
    }

    /**
     * Displays link to the context help.
     * @param $str   - the string that the context help is related to (already escaped)
     * @param $help  - help section identifier
     * @param $do_print true to echo, false to return
     */
    public function printHelp($str, $help = null, $do_print = true)
    {
        //\PC::debug(['str' => $str, 'help' => $help], 'printHelp');
        if ($help !== null) {
            $helplink = $this->getHelpLink($help);
            $str .= '<a class="help" href="' . $helplink . '" title="' . $this->lang['strhelp'] . '" target="phppgadminhelp">' . $this->lang['strhelpicon'] . '</a>';

        }
        if ($do_print) {
            echo $str;
        } else {
            return $str;
        }
    }

    public function getHelpLink($help)
    {
        return htmlspecialchars(SUBFOLDER . '/help?help=' . urlencode($help) . '&server=' . urlencode($this->server_id));

    }

    /**
     * Print out the page heading and help link
     * @param $title Title, already escaped
     * @param $help (optional) The identifier for the help link
     */
    public function printTitle($title, $help = null, $do_print = true)
    {
        $data = $this->data;
        $lang = $this->lang;

        $title_html = '<h2>';
        $title_html .= $this->printHelp($title, $help, false);
        $title_html .= "</h2>\n";

        if ($do_print) {
            echo $title_html;
        } else {
            return $title_html;
        }
    }

}
