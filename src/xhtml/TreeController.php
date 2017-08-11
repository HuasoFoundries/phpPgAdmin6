<?php

namespace PHPPgAdmin\XHtml;

use \PHPPgAdmin\Decorators\Decorator;

/**
 * Base TreeController controller class
 */
class TreeController
{
    use \PHPPgAdmin\HelperTrait;

    private $container      = null;
    private $data           = null;
    private $database       = null;
    private $server_id      = null;
    public $form            = '';
    public $href            = '';
    public $lang            = [];
    public $action          = '';
    public $_name           = 'TreeController';
    public $controller_name = 'TreeController';
    public $_title          = 'base';

    /* Constructor */
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
        if ($controller_name !== null) {
            $this->controller_name = $controller_name;
        }
        //\PC::debug($this->_name, 'instanced controller');
    }

    /** Produce XML data for the browser tree
     * @param $treedata A set of records to populate the tree.
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
     */
    public function printTree(&$_treedata, &$attrs, $section)
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

        $this->printTreeXML($treedata, $attrs);
    }

    /** Produce XML data for the browser tree
     * @param $treedata A set of records to populate the tree.
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
     */
    private function printTreeXML(&$treedata, &$attrs)
    {
        $lang = $this->lang;

        header('Content-Type: text/xml; charset=UTF-8');
        header('Cache-Control: no-cache');

        echo "<tree>\n";

        /*$this->prtrace([
        'treedata' => $treedata,
        'attrs'    => $attrs,
        ]);*/

        if (count($treedata) > 0) {
            foreach ($treedata as $rec) {

                echo '<tree';
                echo Decorator::value_xml_attr('text', $attrs['text'], $rec);
                echo Decorator::value_xml_attr('action', $attrs['action'], $rec);
                echo Decorator::value_xml_attr('src', $attrs['branch'], $rec);

                $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['icon'], $rec));
                echo Decorator::value_xml_attr('icon', $icon, $rec);
                echo Decorator::value_xml_attr('iconaction', $attrs['iconAction'], $rec);

                if (!empty($attrs['openicon'])) {
                    $icon = $this->misc->icon(Decorator::get_sanitized_value($attrs['openIcon'], $rec));
                }
                echo Decorator::value_xml_attr('openicon', $icon, $rec);

                echo Decorator::value_xml_attr('tooltip', $attrs['toolTip'], $rec);

                echo " />\n";
            }
        } else {
            $msg = isset($attrs['nodata']) ? $attrs['nodata'] : $lang['strnoobjects'];
            echo "<tree text=\"{$msg}\" onaction=\"tree.getSelected().getParent().reload()\" icon=\"", $this->misc->icon('ObjectNotFound'), '" />' . "\n";
        }

        echo "</tree>\n";
    }

    public function adjustTabsForTree(&$tabs)
    {

        foreach ($tabs as $i => $tab) {
            if ((isset($tab['hide']) && $tab['hide'] === true) || (isset($tab['tree']) && $tab['tree'] === false)) {
                unset($tabs[$i]);
            }
        }
        return new \PHPPgAdmin\ArrayRecordSet($tabs);
    }

}
