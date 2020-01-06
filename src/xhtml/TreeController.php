<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

namespace PHPPgAdmin\XHtml;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base TreeController controller class.
 */
class TreeController
{
    use \PHPPgAdmin\Traits\HelperTrait;

    protected $container;
    public $form             = '';
    public $href             = '';
    public $lang             = [];
    public $action           = '';
    public $controller_name  = 'TreeController';
    public $controller_title = 'base';

    // Constructor
    public function __construct(\Slim\Container $container, $controller_name = null)
    {
        $this->container = $container;
        $this->lang      = $container->get('lang');
        //$this->conf           = $container->get('conf');
        $this->view           = $container->get('view');
        $this->plugin_manager = $container->get('plugin_manager');
        $this->appName        = $container->get('settings')['appName'];
        $this->appVersion     = $container->get('settings')['appVersion'];
        $this->appLangFiles   = $container->get('appLangFiles');
        $this->misc           = $container->get('misc');
        $this->conf           = $this->misc->getConf();
        $this->appThemes      = $container->get('appThemes');
        $this->action         = $container->get('action');
        if (null !== $controller_name) {
            $this->controller_name = $controller_name;
        }

    }

    /**
     * Produce JSON data for the browser tree.
     *
     * @param \PHPPgAdmin\ArrayRecordSet $_treedata a set of records to populate the tree
     * @param array                      $attrs     Attributes for tree items
     *                                              'text' - the text for the tree node
     *                                              'icon' - an icon for node
     *                                              'openIcon' - an alternative icon when the node is expanded
     *                                              'toolTip' - tool tip text for the node
     *                                              'action' - URL to visit when single clicking the node
     *                                              'iconAction' - URL to visit when single clicking the icon node
     *                                              'branch' - URL for child nodes (tree XML)
     *                                              'expand' - the action to return XML for the subtree
     *                                              'nodata' - message to display when node has no children
     * @param string                     $section   The section where the branch is linked in the tree
     * @param bool                       $print     either to return or echo the result
     *
     * @return \Slim\Http\Response|string the json rendered tree
     */
    public function printTree(&$_treedata, &$attrs, $section, $print = true)
    {
        $plugin_manager = $this->plugin_manager;

        $treedata = [];

        if ($_treedata->recordCount() > 0) {
            while (!$_treedata->EOF) {
                $treedata[] = $_treedata->fields;
                $_treedata->moveNext();
            }
        }

        $tree_params = [
            'treedata' => &$treedata,
            'attrs'    => &$attrs,
            'section'  => $section,
        ];

        $plugin_manager->doHook('tree', $tree_params);

        return $this->printTreeJSON($treedata, $attrs, $print);
    }

    /**
     * Produce JSON data for the browser tree.
     *
     * @param array $treedata a set of records to populate the tree
     * @param array $attrs    Attributes for tree items
     *                        'text' - the text for the tree node
     *                        'icon' - an icon for node
     *                        'openIcon' - an alternative icon when the node is expanded
     *                        'toolTip' - tool tip text for the node
     *                        'action' - URL to visit when single clicking the node
     *                        'iconAction' - URL to visit when single clicking the icon node
     *                        'branch' - URL for child nodes (tree JSON)
     *                        'expand' - the action to return JSON for the subtree
     *                        'nodata' - message to display when node has no children
     * @param bool  $print    either to return or echo the result
     *
     * @return \Slim\Http\Response|string the json rendered tree
     */
    private function printTreeJSON(&$treedata, &$attrs, $print = true)
    {
        $parent = [];

        if (isset($attrs['is_root'])) {
            $parent = [
                'id'       => 'root',
                'children' => true,
                'icon'     => \SUBFOLDER . '/assets/images/themes/default/Servers.png',
                'state'    => ['opened' => true],
                'a_attr'   => ['href' => str_replace('//', '/', \SUBFOLDER . '/src/views/servers')],
                'url'      => str_replace('//', '/', \SUBFOLDER . '/src/views/servers?action=tree'),
                'text'     => 'Servers',
            ];
        } elseif (count($treedata) > 0) {
            foreach ($treedata as $rec) {
                $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['icon'], $rec));
                if (!empty($attrs['openicon'])) {
                    $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['openIcon'], $rec));
                }

                $tree = [
                    'text'       => Decorator::get_sanitized_value($attrs['text'], $rec),
                    'id'         => sha1(Decorator::get_sanitized_value($attrs['action'], $rec)),
                    'icon'       => Decorator::get_sanitized_value($icon, $rec),
                    'iconaction' => Decorator::get_sanitized_value($attrs['iconAction'], $rec),
                    'openicon'   => Decorator::get_sanitized_value($icon, $rec),
                    'tooltip'    => Decorator::get_sanitized_value($attrs['toolTip'], $rec),
                    'a_attr'     => ['href' => Decorator::get_sanitized_value($attrs['action'], $rec)],
                    'children'   => false,
                ];
                $url = Decorator::get_sanitized_value($attrs['branch'], $rec);
                if ($url && strpos($url, '/src/views') === false) {
                    $url = str_replace('//', '/', \SUBFOLDER . '/src/views/' . $url);
                }
                if ($url) {
                    $tree['url']      = $url;
                    $tree['children'] = true;
                }

                //$tree['text'] = '<a href="' . $tree['id'] . '" target="detail">' . $tree['text'] . '</a>';

                $parent[] = $tree;
            }
        } else {
            $parent = ['children' => false];
        }

        if (true === $print) {
            if (isset($_REQUEST['children'])) {
                $children = $parent;
                $parent   = ['children' => $children];
            }

            return $this
                ->container
                ->responseobj
                ->withStatus(200)
                ->withJson($parent);
        }

        return $parent;
    }

    /**
     * Hides or show tree tabs according to their properties.
     *
     * @param array $tabs The tabs
     *
     * @return \PHPPgAdmin\ArrayRecordSet filtered tabs in the form of an ArrayRecordSet
     */
    public function adjustTabsForTree(&$tabs)
    {
        foreach ($tabs as $i => $tab) {
            if ((isset($tab['hide']) && true === $tab['hide']) || (isset($tab['tree']) && false === $tab['tree'])) {
                unset($tabs[$i]);
            }
        }

        return new \PHPPgAdmin\ArrayRecordSet($tabs);
    }
}
