<?php

/**
 * PHPPgAdmin v6.0.0-RC6
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class HelpController extends BaseController
{
    public $controller_title = 'strhelppagebrowser';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        switch ($this->action) {
            case 'browse':
                $this->doBrowse();

                break;
            default:
                $this->doDefault();

                break;
        }
    }

    public function doDefault()
    {
        $data = $this->misc->getDatabaseAccessor();

        if (isset($_REQUEST['help'])) {
            $url = $data->getHelp($_REQUEST['help']);

            if (is_array($url)) {
                $this->doChoosePage($url);

                return;
            }

            if ($url) {
                header("Location: ${url}");

                return;
            }
        }

        $this->doBrowse($this->lang['strinvalidhelppage']);
    }

    public function doBrowse($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printHeader();
        $this->printBody();

        $this->printTitle($this->lang['strselecthelppage']);

        echo $this->printMsg($msg);

        echo '<dl>'.PHP_EOL;

        $pages = $data->getHelpPages();
        foreach ($pages as $page => $dummy) {
            echo "<dt>{$page}</dt>".PHP_EOL;

            $urls = $data->getHelp($page);
            if (!is_array($urls)) {
                $urls = [$urls];
            }

            foreach ($urls as $url) {
                echo "<dd><a href=\"{$url}\">{$url}</a></dd>".PHP_EOL;
            }
        }

        echo '</dl>'.PHP_EOL;

        $this->printFooter();
    }

    public function doChoosePage($urls)
    {
        $this->printHeader();
        $this->printBody();

        $this->printTitle($this->lang['strselecthelppage']);

        echo '<ul>'.PHP_EOL;
        foreach ($urls as $url) {
            echo "<li><a href=\"{$url}\">{$url}</a></li>".PHP_EOL;
        }
        echo '</ul>'.PHP_EOL;

        $this->printFooter();
    }
}
