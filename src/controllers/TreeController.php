<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Controller;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base TreeController controller class.
 */
class TreeController extends BaseController
{
    use \PHPPgAdmin\Traits\HelperTrait;

    public $form = '';

    public $href = '';

    public $lang = [];

    public $action = '';

    public $controller_name = 'TreeController';

    public $controller_title = 'base';

    public $misc;

    public $conf;

    public $appThemes;

    public $view;

    public $appVersion;

    public $appLangFiles;

    protected $container;

    // Constructor
    public function __construct(\PHPPgAdmin\ContainerUtils $container, $controller_name = null)
    {
        $this->container = $container;
        $this->lang = $container->get('lang');
        //$this->conf           = $container->get('conf');
        $this->view = $container->get('view');

        $this->appName = $container->get('settings')['appName'];
        $this->appVersion = $container->get('settings')['appVersion'];
        $this->appLangFiles = $container->get('appLangFiles');
        $this->misc = $container->get('misc');
        $this->conf = $this->misc->getConf();
        $this->appThemes = $container->get('appThemes');
        $this->action = $container->get('action');

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
        $treedata = [];

        if (0 < $_treedata->recordCount()) {
            while (!$_treedata->EOF) {
                $treedata[] = $_treedata->fields;
                $_treedata->moveNext();
            }
        }

        $tree_params = [
            'treedata' => &$treedata,
            'attrs' => &$attrs,
            'section' => $section,
        ];

        return $this->printTreeJSON($treedata, $attrs, $print);
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
     * @return array<int|string, array<string, mixed>|bool|string> the json rendered tree
     */
    private function printTreeJSON(&$treedata, &$attrs, $print = true)
    {
        $parent = [];

        if (isset($attrs['is_root'])) {
            $parent = [
                'id' => 'root',
                'children' => true,
                'icon' => \containerInstance()->subFolder . '/assets/images/themes/default/Servers.png',
                'state' => ['opened' => true],
                'a_attr' => ['href' => \str_replace('//', '/', \containerInstance()->subFolder . '/src/views/servers')],
                'url' => \str_replace('//', '/', \containerInstance()->subFolder . '/src/views/servers?action=tree'),
                'text' => 'Servers',
            ];
        } elseif (0 < \count($treedata)) {
            foreach ($treedata as $rec) {
                $icon = $this->view->icon(Decorator::get_sanitized_value($attrs['icon'], $rec));

                if (!empty($attrs['openicon'])) {
                    $icon = $this->view->icon(Decorator::get_sanitized_value($attrs['openIcon'], $rec));
                }

                $tree = [
                    'text' => Decorator::get_sanitized_value($attrs['text'], $rec),
                    'id' => \sha1(Decorator::get_sanitized_value($attrs['action'], $rec)),
                    'icon' => Decorator::get_sanitized_value($icon, $rec),
                    'iconaction' => Decorator::get_sanitized_value($attrs['iconAction'], $rec),
                    'openicon' => Decorator::get_sanitized_value($icon, $rec),
                    'tooltip' => Decorator::get_sanitized_value($attrs['toolTip'], $rec),
                    'a_attr' => ['href' => Decorator::get_sanitized_value($attrs['action'], $rec)],
                    'children' => false,
                ];
                $url = Decorator::get_sanitized_value($attrs['branch'], $rec);

                if ($url && false === \mb_strpos($url, '/src/views')) {
                    $url = \str_replace('//', '/', \containerInstance()->subFolder . '/src/views/' . $url);
                }

                if ($url) {
                    $tree['url'] = $url;
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
                $parent = ['children' => $children];
            }

            return $this
                ->container
                ->response
                ->withStatus(200)
                ->withJson($parent);
        }

        return $parent;
    }
}
