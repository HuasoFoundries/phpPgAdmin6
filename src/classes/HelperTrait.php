<?php

    namespace PHPPgAdmin;

    trait HelperTrait
    {
        public static function statictrace()
        {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $btarray0 = [
                'class'    => $backtrace[1]['class'],
                'type'     => $backtrace[1]['type'],
                'function' => $backtrace[1]['function'],
                'spacer'   => ' ',
                'line'     => $backtrace[0]['line'],
            ];

            $tag = implode('', $btarray0);

            \PC::debug(func_get_args(), $tag);
        }

        /**
         * Receives N parameters and sends them to the console adding where was it called from
         */
        public function prtrace()
        {

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $btarray0 = [
                'class'    => $backtrace[1]['class'],
                'type'     => $backtrace[1]['type'],
                'function' => $backtrace[1]['function'],
                'spacer'   => ' ',
                'line'     => $backtrace[0]['line'],
            ];

            $tag = implode('', $btarray0);

            \PC::debug(func_get_args(), $tag);
        }

        /**
         * Prints the page header.  If member variable $this->_no_output is
         * set then no header is drawn.
         *
         * @param \PHPPgAdmin\The|string $title    The title of the page
         * @param                        $script   script tag
         * @param                        $do_print boolean if false, the function will return the header content
         * @param string                 $template
         * @return string
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
         *
         * @param bool|True $doBody    True to output body tag, false to return
         * @param string    $bodyClass - name of body class
         * @return string
         */
        public function printBody($doBody = true, $bodyClass = 'detailbody')
        {

            $bodyClass = htmlspecialchars($bodyClass);
            $bodyHtml  = '<body data-controller="' . $this->_name . '" class="' . $bodyClass . '" >';
            $bodyHtml  .= "\n";

            if (!$this->_no_output && $doBody) {
                echo $bodyHtml;
            } else {
                return $bodyHtml;
            }
        }

        /**
         * Print out the page heading and help link
         *
         * @param      $title Title, already escaped
         * @param      $help  (optional) The identifier for the help link
         * @param bool $do_print
         * @return string
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

        /**
         * Displays link to the context help.
         *
         * @param           $str      - the string that the context help is related to (already escaped)
         * @param           $help     - help section identifier
         * @param bool|true $do_print true to echo, false to return
         * @return string
         */
        public function printHelp($str, $help = null, $do_print = true)
        {
            //\PC::debug(['str' => $str, 'help' => $help], 'printHelp');
            if ($help !== null) {
                $helplink = $this->getHelpLink($help);
                $str      .= '<a class="help" href="' . $helplink . '" title="' . $this->lang['strhelp'] . '" target="phppgadminhelp">' . $this->lang['strhelpicon'] . '</a>';
            }
            if ($do_print) {
                echo $str;
            } else {
                return $str;
            }
        }

        public function getHelpLink($help)
        {
            return htmlspecialchars('/src/views/help.php?help=' . urlencode($help) . '&server=' . urlencode($this->server_id));
        }

        /**
         * Prints the page footer
         *
         * @param bool|True $doBody True to output body tag, false to return the html
         * @param string    $template
         * @return string
         */
        public function printFooter($doBody = true, $template = 'footer.twig')
        {
            $lang = $this->lang;

            $footer_html = '';
            //$this->prtrace(['$_reload_browser' => $this->_reload_browser, 'template' => $template]);
            if ($this->_reload_browser) {
                $footer_html .= $this->printReload(false, false);
            } elseif ($this->_reload_drop_database) {
                $footer_html .= $this->printReload(true, false);
            }
            if (!$this->_no_bottom_link) {
                $footer_html .= '<a data-footertemplate="' . $template . '" href="#" class="bottom_link">' . $lang['strgotoppage'] . '</a>';
            }

            $footer_html .= $this->view->fetch($template);

            if ($doBody) {
                echo $footer_html;
            } else {
                return $footer_html;
            }
        }

        /**
         * Outputs JavaScript code that will reload the browser
         *
         * @param           $database True if dropping a database, false otherwise
         * @param bool|true $do_print true to echo, false to return;
         * @return string
         */
        public function printReload($database, $do_print = true)
        {

            $reload = "<script type=\"text/javascript\">\n";
            //$reload .= " alert('will reload');";
            if ($database) {
                $reload .= "\tparent.frames && parent.frames.browser && parent.frames.browser.location.href=\"/src/views/browser.php\";\n";
            } else {
                $reload .= "\tif(parent.frames && parent.frames.browser) { parent.frames.browser.location.reload();} else { location.replace(location.href);}\n";
                //$reload .= "\tparent.frames.detail.location.href=\"/src/views/intro\";\n";
                //$reload .= "\tparent.frames.detail.location.reload();\n";
            }

            $reload .= "</script>\n";
            if ($do_print) {
                echo $reload;
            } else {
                return $reload;
            }
        }

        /**
         * Outputs JavaScript to set default focus
         *
         * @param $object eg. forms[0].username
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
         * @param                     $name      the window name
         * @param bool|\PHPPgAdmin\if $addServer if true (default) then the server id is
         *                                       attached to the name.
         */
        public function setWindowName($name, $addServer = true)
        {
            echo "<script type=\"text/javascript\">\n";
            echo "//<![CDATA[\n";
            echo "   window.name = '{$name}", ($addServer ? ':' . htmlspecialchars($this->server_id) : ''), "';\n";
            echo "//]]>\n";
            echo "</script>\n";
        }

    }