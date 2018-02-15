<?php

/*
 * PHPPgAdmin v6.0.0-beta.30
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base controller class.
 */
class HistoryController extends BaseController
{
    public $controller_name = 'HistoryController';

    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $lang   = $this->lang;
        $action = $this->action;

        $data = $this->misc->getDatabaseAccessor();

        $this->scripts = '<script type="text/javascript">window.inPopUp=true;</script>';

        switch ($action) {
            case 'confdelhistory':
                $this->doDelHistory($_REQUEST['queryid'], true);

                break;
            case 'delhistory':
                if (isset($_POST['yes'])) {
                    $this->doDelHistory($_REQUEST['queryid'], false);
                }

                $this->doDefault();

                break;
            case 'confclearhistory':
                $this->doClearHistory(true);

                break;
            case 'clearhistory':
                if (isset($_POST['yes'])) {
                    $this->doClearHistory(false);
                }

                $this->doDefault();

                break;
            case 'download':
                return $this->doDownloadHistory();
            default:
                $this->doDefault();
        }

        // Set the name of the window
        $this->setWindowName('history');

        return $this->printFooter();
    }

    public function doDefault()
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        $onchange = "onchange=\"location.href='" . \SUBFOLDER . "/src/views/history.php?server=' + encodeURI(server.options[server.selectedIndex].value) + '&amp;database=' + encodeURI(database.options[database.selectedIndex].value) + '&amp;'\"";

        $this->printHeader($lang['strhistory'], $this->scripts, true, 'header.twig');

        // Bring to the front always
        echo "<body onload=\"window.focus();\">\n";

        echo '<form action="' . \SUBFOLDER . "/src/views/history.php\" method=\"post\">\n";
        $this->misc->printConnection($onchange);
        echo '</form><br />';

        if (!isset($_REQUEST['database'])) {
            echo "<p>{$lang['strnodatabaseselected']}</p>\n";

            return;
        }

        if (isset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']])) {
            $history = new \PHPPgAdmin\ArrayRecordSet($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']]);

            //Kint::dump($history);
            $columns = [
                'query'    => [
                    'title' => $lang['strsql'],
                    'field' => Decorator::field('query'),
                ],
                'paginate' => [
                    'title' => $lang['strpaginate'],
                    'field' => Decorator::field('paginate'),
                    'type'  => 'yesno',
                ],
                'actions'  => [
                    'title' => $lang['stractions'],
                ],
            ];

            $actions = [
                'run'    => [
                    'content' => $lang['strexecute'],
                    'attr'    => [
                        'href'   => [
                            'url'     => 'sql.php',
                            'urlvars' => [
                                'subject'   => 'history',
                                'nohistory' => 't',
                                'queryid'   => Decorator::field('queryid'),
                                'paginate'  => Decorator::field('paginate'),
                            ],
                        ],
                        'target' => 'detail',
                    ],
                ],
                'remove' => [
                    'content' => $lang['strdelete'],
                    'attr'    => [
                        'href' => [
                            'url'     => 'history.php',
                            'urlvars' => [
                                'action'  => 'confdelhistory',
                                'queryid' => Decorator::field('queryid'),
                            ],
                        ],
                    ],
                ],
            ];

            echo $this->printTable($history, $columns, $actions, 'history-history', $lang['strnohistory']);
        } else {
            echo "<p>{$lang['strnohistory']}</p>\n";
        }

        $navlinks = [
            'refresh' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'history.php',
                        'urlvars' => [
                            'action'   => 'history',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                        ],
                    ],
                ],
                'content' => $lang['strrefresh'],
            ],
        ];

        if (isset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']])
            && count($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']])) {
            $navlinks['download'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'history.php',
                        'urlvars' => [
                            'action'   => 'download',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                        ],
                    ],
                ],
                'content' => $lang['strdownload'],
            ];
            $navlinks['clear'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'history.php',
                        'urlvars' => [
                            'action'   => 'confclearhistory',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                        ],
                    ],
                ],
                'content' => $lang['strclearhistory'],
            ];
        }

        $this->printNavLinks($navlinks, 'history-history', get_defined_vars());
    }

    public function doDelHistory($qid, $confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printHeader($lang['strhistory'], $this->scripts);

            // Bring to the front always
            echo "<body onload=\"window.focus();\">\n";

            echo "<h3>{$lang['strdelhistory']}</h3>\n";
            echo "<p>{$lang['strconfdelhistory']}</p>\n";

            echo '<pre>', htmlentities($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']][$qid]['query'], ENT_QUOTES, 'UTF-8'), '</pre>';
            echo '<form action="' . \SUBFOLDER . "/src/views/history.php\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"delhistory\" />\n";
            echo "<input type=\"hidden\" name=\"queryid\" value=\"${qid}\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
            echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
            echo "</form>\n";
        } else {
            unset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']][$qid]);
        }
    }

    public function doClearHistory($confirm)
    {
        $lang = $this->lang;
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printHeader($lang['strhistory'], $this->scripts);

            // Bring to the front always
            echo "<body onload=\"window.focus();\">\n";

            echo "<h3>{$lang['strclearhistory']}</h3>\n";
            echo "<p>{$lang['strconfclearhistory']}</p>\n";

            echo '<form action="' . \SUBFOLDER . "/src/views/history.php\" method=\"post\">\n";
            echo "<input type=\"hidden\" name=\"action\" value=\"clearhistory\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"yes\" value=\"{$lang['stryes']}\" />\n";
            echo "<input type=\"submit\" name=\"no\" value=\"{$lang['strno']}\" />\n";
            echo "</form>\n";
        } else {
            unset($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']]);
        }
    }

    public function doDownloadHistory()
    {
        header('Content-Type: application/download');
        $datetime = date('YmdHis');
        header("Content-Disposition: attachment; filename=history{$datetime}.sql");

        foreach ($_SESSION['history'][$_REQUEST['server']][$_REQUEST['database']] as $queries) {
            $query = rtrim($queries['query']);
            echo $query;
            if (';' != substr($query, -1)) {
                echo ';';
            }

            echo "\n";
        }
    }
}
