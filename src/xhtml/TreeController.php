<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin\XHtml;

use PHPPgAdmin\Decorators\Decorator;

/**
 * Base TreeController controller class.
 */
class TreeController
{
    use \PHPPgAdmin\HelperTrait;

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
        //\PC::debug($this->controller_name, 'instanced controller');
    }

    /** Produce XML data for the browser tree
     * @param $treedata a set of records to populate the tree
     * @param $attrs Attributes for tree items
     *        'text' - the text for the tree node
     *        'icon' - an icon for node
     *        'openIcon' - an alternative icon when the node is expanded
     *        'toolTip' - tool tip text for the node
     *        'action' - URL to visit when single clicking the node
     *        'iconAction' - URL to visit when single clicking the icon node
     *        'branch' - URL for child nodes (tree XML)
     *        'expand' - the action to return XML for the subtree
     *        'nodata' - message to display when node has no children
     * @param $section The section where the branch is linked in the tree
     * @param mixed $print
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

        $plugin_manager->do_hook('tree', $tree_params);

        if (isset($_REQUEST['json'])) {
            return $this->printTreeJSON($treedata, $attrs, $print);
        }

        return $this->printTreeXML($treedata, $attrs, $print);
    }

    /** Produce XML data for the browser tree
     * @param $treedata a set of records to populate the tree
     * @param $attrs Attributes for tree items
     *        'text' - the text for the tree node
     *        'icon' - an icon for node
     *        'openIcon' - an alternative icon when the node is expanded
     *        'toolTip' - tool tip text for the node
     *        'action' - URL to visit when single clicking the node
     *        'iconAction' - URL to visit when single clicking the icon node
     *        'branch' - URL for child nodes (tree XML)
     *        'expand' - the action to return XML for the subtree
     *        'nodata' - message to display when node has no children
     * @param mixed $print
     */
    private function printTreeXML(&$treedata, &$attrs, $print = true)
    {
        $lang = $this->lang;

        $tree_xml = '<?xml version="1.0" encoding="UTF-8"?><tree>';

        if (count($treedata) > 0) {
            foreach ($treedata as $rec) {
                $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['icon'], $rec));
                if (!empty($attrs['openicon'])) {
                    $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['openIcon'], $rec));
                }
                $tree_xml .= '<tree>';

                $text_xml       = Decorator::value_xml_attr_tag('text', $attrs['text'], $rec);
                $action_xml     = Decorator::value_xml_attr_tag('action', $attrs['action'], $rec);
                $src_xml        = Decorator::value_xml_attr_tag('src', $attrs['branch'], $rec);
                $icon_xml       = Decorator::value_xml_attr_tag('icon', $icon, $rec);
                $iconaction_xml = Decorator::value_xml_attr_tag('iconaction', $attrs['iconAction'], $rec);
                $openicon_xml   = Decorator::value_xml_attr_tag('openicon', $icon, $rec);
                $tooltip_xml    = Decorator::value_xml_attr_tag('tooltip', $attrs['toolTip'], $rec);

                $tree_xml .= $text_xml;
                $tree_xml .= $action_xml;
                $tree_xml .= $src_xml;
                $tree_xml .= $icon_xml;
                $tree_xml .= $iconaction_xml;
                $tree_xml .= $openicon_xml;
                $tree_xml .= $tooltip_xml;

                $tree_xml .= '</tree>';

            }
        } else {
            $msg = isset($attrs['nodata']) ? $attrs['nodata'] : $lang['strnoobjects'];
            $tree_xml .= "<tree text=\"{$msg}\" onaction=\"tree.getSelected().getParent().reload()\" icon=\"" . $this->misc->icon('ObjectNotFound') . '" />' . "\n";
        }

        $tree_xml .= '</tree>';
        if (true === $print) {

            if (null === $this->container->requestobj->getAttribute('route')) {
                header('Content-Type: text/xml; charset=UTF-8');
                header('Cache-Control: no-cache');
                echo $tree_xml;
            } else {
                return $this
                    ->container
                    ->responseobj
                    ->withStatus(200)
                    ->withHeader('Content-Type', 'text/xml; charset=UTF-8')
                    ->write($tree_xml);
            }
        } else {
            return $tree_xml;
        }
    }

    /** Produce XML data for the browser tree
     * @param $treedata a set of records to populate the tree
     * @param $attrs Attributes for tree items
     *        'text' - the text for the tree node
     *        'icon' - an icon for node
     *        'openIcon' - an alternative icon when the node is expanded
     *        'toolTip' - tool tip text for the node
     *        'action' - URL to visit when single clicking the node
     *        'iconAction' - URL to visit when single clicking the icon node
     *        'branch' - URL for child nodes (tree XML)
     *        'expand' - the action to return XML for the subtree
     *        'nodata' - message to display when node has no children
     * @param mixed $print
     */
    private function printTreeJSON(&$treedata, &$attrs, $print = true)
    {
        $lang = $this->lang;

        $parent = [];

        if (count($treedata) > 0) {
            foreach ($treedata as $rec) {
                $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['icon'], $rec));
                if (!empty($attrs['openicon'])) {
                    $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['openIcon'], $rec));
                }

                $tree = [
                    'text'           => Decorator::get_sanitized_value($attrs['text'], $rec),
                    'action_xml'     => Decorator::get_sanitized_value($attrs['action'], $rec),

                    'icon'           => Decorator::get_sanitized_value($icon, $rec),
                    'iconaction_xml' => Decorator::get_sanitized_value($attrs['iconAction'], $rec),
                    'openicon_xml'   => Decorator::get_sanitized_value($icon, $rec),
                    'tooltip_xml'    => Decorator::get_sanitized_value($attrs['toolTip'], $rec),
                    'children'       => false,
                ];
                $url = Decorator::get_sanitized_value($attrs['branch'], $rec);
                if (strpos($url, '/src/views') === false) {
                    $url = '/src/views/' . $url;
                }
                if ($url) {
                    $tree['url']      = $url;
                    $tree['children'] = true;
                    $tree['id']       = $url; //str_replace(' ', '_', $tree['text']);
                }
                $tree['text'] = '<a href="' . $tree['action_xml'] . '" target="detail">' . $tree['text'] . '</a>';

                $parent[] = $tree;
            }
        } else {
            $parent = ['children' => false];
        }

        if (true === $print) {

            if (null === $this->container->requestobj->getAttribute('route')) {
                header('Content-Type: text/xml; charset=UTF-8');
                header('Cache-Control: no-cache');
                echo $tree_xml;
            } else {
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
        } else {
            return $tree_xml;
        }
    }

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
