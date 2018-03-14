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
    public $form = '';
    public $href = '';
    public $lang = [];
    public $action = '';
    public $controller_name = 'TreeController';
    public $controller_title = 'base';

    // Constructor
    public function __construct(\Slim\Container $container, $controller_name = null)
    {
        $this->container = $container;
        $this->lang = $container->get('lang');
        //$this->conf           = $container->get('conf');
        $this->view = $container->get('view');
        $this->plugin_manager = $container->get('plugin_manager');
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
            'attrs' => &$attrs,
            'section' => $section,
        ];

        $plugin_manager->do_hook('tree', $tree_params);

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

        $tree_xml = "<tree>\n";

        if (count($treedata) > 0) {
            foreach ($treedata as $rec) {
                $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['icon'], $rec));
                if (!empty($attrs['openicon'])) {
                    $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['openIcon'], $rec));
                }

                $tree_xml .= '<tree';
                /*$tree_xml .= Decorator::value_xml_attr('text', $attrs['text'], $rec);
                $tree_xml .= Decorator::value_xml_attr('action', $attrs['action'], $rec);
                $tree_xml .= Decorator::value_xml_attr('src', $attrs['branch'], $rec);
                $tree_xml .= Decorator::value_xml_attr('icon', $icon, $rec);
                $tree_xml .= Decorator::value_xml_attr('iconaction', $attrs['iconAction'], $rec);
                $tree_xml .= Decorator::value_xml_attr('openicon', $icon, $rec);
                $tree_xml .= Decorator::value_xml_attr('tooltip', $attrs['toolTip'], $rec);*/

                $tree_xml .= ' >';

                $tree_xml .= Decorator::value_xml_attr_tag('text', $attrs['text'], $rec);
                $tree_xml .= Decorator::value_xml_attr_tag('action', $attrs['action'], $rec);
                $tree_xml .= Decorator::value_xml_attr_tag('src', $attrs['branch'], $rec);
                $tree_xml .= Decorator::value_xml_attr_tag('icon', $icon, $rec);
                $tree_xml .= Decorator::value_xml_attr_tag('iconaction', $attrs['iconAction'], $rec);
                $tree_xml .= Decorator::value_xml_attr_tag('openicon', $icon, $rec);
                $tree_xml .= Decorator::value_xml_attr_tag('tooltip', $attrs['toolTip'], $rec);

                $tree_xml .= "</tree>\n";
            }
        } else {
            $msg = isset($attrs['nodata']) ? $attrs['nodata'] : $lang['strnoobjects'];
            $tree_xml .= "<tree text=\"{$msg}\" onaction=\"tree.getSelected().getParent().reload()\" icon=\"".$this->misc->icon('ObjectNotFound').'" />'."\n";
        }

        $tree_xml .= "</tree>\n";
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
                    ->withHeader('Content-Type', 'text/xml;charset=utf-8')
                    ->write($tree_xml);
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
