<?php

namespace PHPPgAdmin\Controller;

/**
 * Base controller class
 */
class HelpController extends BaseController
{
    public $_name = 'HelpController';

    public function render()
    {
        $action = $this->action;

        switch ($action) {
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
        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;
        $data = $misc->getDatabaseAccessor();

        if (isset($_REQUEST['help'])) {
            $url = $data->getHelp($_REQUEST['help']);

            \PC::debug(['url' => $url], 'HelpController::doDefault');
            if (is_array($url)) {
                $this->doChoosePage($url);
                return;
            }

            if ($url) {
                header("Location: $url");
                return;
            }
        }

        $this->doBrowse($lang['strinvalidhelppage']);
    }

    public function doBrowse($msg = '')
    {
        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;
        $data = $misc->getDatabaseAccessor();

        $this->printHeader($lang['strhelppagebrowser']);
        $this->printBody();

        $this->printTitle($lang['strselecthelppage']);

        echo $this->printMsg($msg);

        echo "<dl>\n";

        $pages = $data->getHelpPages();
        foreach ($pages as $page => $dummy) {
            echo "<dt>{$page}</dt>\n";

            $urls = $data->getHelp($page);
            if (!is_array($urls)) {
                $urls = [$urls];
            }

            foreach ($urls as $url) {
                echo "<dd><a href=\"{$url}\">{$url}</a></dd>\n";
            }
        }

        echo "</dl>\n";

        $this->printFooter();
    }

    public function doChoosePage($urls)
    {
        $conf = $this->conf;
        $misc = $this->misc;
        $lang = $this->lang;
        $data = $misc->getDatabaseAccessor();

        $this->printHeader($lang['strhelppagebrowser']);
        $this->printBody();

        $this->printTitle($lang['strselecthelppage']);

        echo "<ul>\n";
        foreach ($urls as $url) {
            echo "<li><a href=\"{$url}\">{$url}</a></li>\n";
        }
        echo "</ul>\n";

        $this->printFooter();
    }
}
