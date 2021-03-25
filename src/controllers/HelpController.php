<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
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

    public function doDefault(): void
    {
        $data = $this->misc->getDatabaseAccessor();

        if (isset($_REQUEST['help'])) {
            $url = $data->getHelp($_REQUEST['help']);

            if (\is_array($url)) {
                $this->doChoosePage($url);

                return;
            }

            if ($url) {
                \header(\sprintf(
                    'Location: %s',
                    $url
                ));

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

        echo '<dl>' . \PHP_EOL;

        $pages = $data->getHelpPages();

        foreach (\array_keys($pages) as $page) {
            echo \sprintf(
                '<dt>%s</dt>',
                $page
            ) . \PHP_EOL;

            $urls = $data->getHelp($page);

            if (!\is_array($urls)) {
                $urls = [$urls];
            }

            foreach ($urls as $url) {
                echo \sprintf(
                    '<dd><a href="%s">%s</a></dd>',
                    $url,
                    $url
                ) . \PHP_EOL;
            }
        }

        echo '</dl>' . \PHP_EOL;

        $this->printFooter();
    }

    public function doChoosePage(array $urls): void
    {
        $this->printHeader();
        $this->printBody();

        $this->printTitle($this->lang['strselecthelppage']);

        echo '<ul>' . \PHP_EOL;

        foreach ($urls as $url) {
            echo \sprintf(
                '<li><a href="%s">%s</a></li>',
                $url,
                $url
            ) . \PHP_EOL;
        }
        echo '</ul>' . \PHP_EOL;

        $this->printFooter();
    }
}
