<?php

/**
 * PHPPgAdmin v6.0.0-beta.47
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 *
 * @package PHPPgAdmin
 */
class AggregatesController extends BaseController
{
    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
    }

    /**
     * Show default list of aggregate functions in the database.
     *
     * @param mixed $msg
     */
    public function doDefault($msg = '')
    {
    }

    public function doTree()
    {
    }

    /**
     * Actually creates the new aggregate in the database.
     */
    public function doSaveCreate()
    {
        $data = $this->misc->getDatabaseAccessor();
        // Check inputs
        if ('' == trim($_REQUEST['name'])) {
            $this->doCreate($this->lang['straggrneedsname']);

            return;
        }
        if ('' == trim($_REQUEST['basetype'])) {
            $this->doCreate($this->lang['straggrneedsbasetype']);

            return;
        }
        if ('' == trim($_REQUEST['sfunc'])) {
            $this->doCreate($this->lang['straggrneedssfunc']);

            return;
        }
        if ('' == trim($_REQUEST['stype'])) {
            $this->doCreate($this->lang['straggrneedsstype']);

            return;
        }

        $status = $data->createAggregate(
            $_REQUEST['name'],
            $_REQUEST['basetype'],
            $_REQUEST['sfunc'],
            $_REQUEST['stype'],
            $_REQUEST['ffunc'],
            $_REQUEST['initcond'],
            $_REQUEST['sortop'],
            $_REQUEST['aggrcomment']
        );

        if (0 == $status) {
            $this->misc->setReloadBrowser(true);
            $this->doDefault($this->lang['straggrcreated']);
        } else {
            $this->doCreate($this->lang['straggrcreatedbad']);
        }
    }

    /**
     * Displays a screen for create a new aggregate function.
     *
     * @param mixed $msg
     */
    public function doCreate($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->coalesceArr($_REQUEST, 'name', '');

        $this->coalesceArr($_REQUEST, 'basetype', '');

        $this->coalesceArr($_REQUEST, 'sfunc', '');

        $this->coalesceArr($_REQUEST, 'stype', '');

        $this->coalesceArr($_REQUEST, 'ffunc', '');

        $this->coalesceArr($_REQUEST, 'initcond', '');

        $this->coalesceArr($_REQUEST, 'sortop', '');

        $this->coalesceArr($_REQUEST, 'aggrcomment', '');

        $this->printTrail('schema');
        $this->printTitle($this->lang['strcreateaggregate'], 'pg.aggregate.create');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/aggregates\" method=\"post\">\n";
        echo "<table>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['strname']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"name\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['name']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['straggrbasetype']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"basetype\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['basetype']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['straggrsfunc']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"sfunc\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['sfunc']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left required\">{$this->lang['straggrstype']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"stype\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['stype']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['straggrffunc']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"ffunc\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['ffunc']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['straggrinitcond']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"initcond\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['initcond']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['straggrsortop']}</th>\n";
        echo "\t\t<td class=\"data\"><input name=\"sortop\" size=\"32\" maxlength=\"{$data->_maxNameLen}\" value=\"",
        htmlspecialchars($_REQUEST['sortop']), "\" /></td>\n\t</tr>\n";
        echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>\n";
        echo "\t\t<td><textarea name=\"aggrcomment\" rows=\"3\" cols=\"32\">",
        htmlspecialchars($_REQUEST['aggrcomment']), "</textarea></td>\n\t</tr>\n";

        echo "</table>\n";
        echo "<p><input type=\"hidden\" name=\"action\" value=\"save_create\" />\n";
        echo $this->misc->form;
        echo "<input type=\"submit\" value=\"{$this->lang['strcreate']}\" />\n";
        echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
        echo "</form>\n";
    }

    /**
     * Function to save after altering an aggregate.
     */
    public function doSaveAlter()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Check inputs
        if ('' == trim($_REQUEST['aggrname'])) {
            $this->doAlter($this->lang['straggrneedsname']);

            return;
        }

        $status = $data->alterAggregate(
            $_REQUEST['aggrname'],
            $_REQUEST['aggrtype'],
            $_REQUEST['aggrowner'],
            $_REQUEST['aggrschema'],
            $_REQUEST['aggrcomment'],
            $_REQUEST['newaggrname'],
            $_REQUEST['newaggrowner'],
            $_REQUEST['newaggrschema'],
            $_REQUEST['newaggrcomment']
        );
        if (0 == $status) {
            $this->doDefault($this->lang['straggraltered']);
        } else {
            $this->doAlter($this->lang['straggralteredbad']);

            return;
        }
    }

    /**
     * Function to allow editing an aggregate function.
     *
     * @param mixed $msg
     */
    public function doAlter($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('aggregate');
        $this->printTitle($this->lang['stralter'], 'pg.aggregate.alter');
        $this->printMsg($msg);

        echo '<form action="'.\SUBFOLDER."/src/views/aggregates\" method=\"post\">\n";
        $aggrdata = $data->getAggregate($_REQUEST['aggrname'], $_REQUEST['aggrtype']);
        if ($aggrdata->recordCount() > 0) {
            // Output table header
            echo "<table>\n";
            echo "\t<tr>\n\t\t<th class=\"data required\">{$this->lang['strname']}</th>";
            echo "<th class=\"data required\">{$this->lang['strowner']}</th>";
            echo "<th class=\"data required\">{$this->lang['strschema']}</th>\n\t</tr>\n";

            // Display aggregate's name, owner and schema
            echo "\t<tr>\n\t\t<td><input name=\"newaggrname\" size=\"32\" maxlength=\"32\" value=\"", htmlspecialchars($_REQUEST['aggrname']), '" /></td>';
            echo '<td><input name="newaggrowner" size="32" maxlength="32" value="', htmlspecialchars($aggrdata->fields['usename']), '" /></td>';
            echo '<td><input name="newaggrschema" size="32" maxlength="32" value="', htmlspecialchars($_REQUEST['schema']), "\" /></td>\n\t</tr>\n";
            echo "\t<tr>\n\t\t<th class=\"data left\">{$this->lang['strcomment']}</th>\n";
            echo "\t\t<td><textarea name=\"newaggrcomment\" rows=\"3\" cols=\"32\">",
            htmlspecialchars($aggrdata->fields['aggrcomment']), "</textarea></td>\n\t</tr>\n";
            echo "</table>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"save_alter\" />\n";
            echo $this->misc->form;
            echo '<input type="hidden" name="aggrname" value="', htmlspecialchars($_REQUEST['aggrname']), "\" />\n";
            echo '<input type="hidden" name="aggrtype" value="', htmlspecialchars($_REQUEST['aggrtype']), "\" />\n";
            echo '<input type="hidden" name="aggrowner" value="', htmlspecialchars($aggrdata->fields['usename']), "\" />\n";
            echo '<input type="hidden" name="aggrschema" value="', htmlspecialchars($_REQUEST['schema']), "\" />\n";
            echo '<input type="hidden" name="aggrcomment" value="', htmlspecialchars($aggrdata->fields['aggrcomment']), "\" />\n";
            echo "<input type=\"submit\" name=\"alter\" value=\"{$this->lang['stralter']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strback']}\" /></p>\n";
        }
        echo "</form>\n";
    }

    /**
     * Show confirmation of drop and perform actual drop of the aggregate function selected.
     *
     * @param mixed $confirm
     */
    public function doDrop($confirm)
    {
        $data = $this->misc->getDatabaseAccessor();

        if ($confirm) {
            $this->printTrail('aggregate');
            $this->printTitle($this->lang['strdrop'], 'pg.aggregate.drop');

            echo '<p>', sprintf($this->lang['strconfdropaggregate'], htmlspecialchars($_REQUEST['aggrname'])), "</p>\n";

            echo '<form action="'.\SUBFOLDER."/src/views/aggregates\" method=\"post\">\n";
            echo "<p><input type=\"checkbox\" id=\"cascade\" name=\"cascade\" /> <label for=\"cascade\">{$this->lang['strcascade']}</label></p>\n";
            echo "<p><input type=\"hidden\" name=\"action\" value=\"drop\" />\n";
            echo '<input type="hidden" name="aggrname" value="', htmlspecialchars($_REQUEST['aggrname']), "\" />\n";
            echo '<input type="hidden" name="aggrtype" value="', htmlspecialchars($_REQUEST['aggrtype']), "\" />\n";
            echo $this->misc->form;
            echo "<input type=\"submit\" name=\"drop\" value=\"{$this->lang['strdrop']}\" />\n";
            echo "<input type=\"submit\" name=\"cancel\" value=\"{$this->lang['strcancel']}\" /></p>\n";
            echo "</form>\n";
        } else {
            $status = $data->dropAggregate($_POST['aggrname'], $_POST['aggrtype'], isset($_POST['cascade']));
            if (0 == $status) {
                $this->misc->setReloadBrowser(true);
                $this->doDefault($this->lang['straggregatedropped']);
            } else {
                $this->doDefault($this->lang['straggregatedroppedbad']);
            }
        }
    }

    /**
     * Show the properties of an aggregate.
     *
     * @param mixed $msg
     */
    public function doProperties($msg = '')
    {
        $data = $this->misc->getDatabaseAccessor();

        $this->printTrail('aggregate');
        $this->printTitle($this->lang['strproperties'], 'pg.aggregate');
        $this->printMsg($msg);

        $aggrdata = $data->getAggregate($_REQUEST['aggrname'], $_REQUEST['aggrtype']);

        if ($aggrdata->recordCount() > 0) {
            // Display aggregate's info
            echo "<table>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['strname']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($_REQUEST['aggrname']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['straggrbasetype']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($_REQUEST['aggrtype']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['straggrsfunc']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggtransfn']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['straggrstype']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggstype']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['straggrffunc']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggfinalfn']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['straggrinitcond']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['agginitval']), "</td>\n</tr>\n";
            if ($data->hasAggregateSortOp()) {
                echo "<tr>\n\t<th class=\"data left\">{$this->lang['straggrsortop']}</th>\n";
                echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['aggsortop']), "</td>\n</tr>\n";
            }
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['strowner']}</th>\n";
            echo "\t<td class=\"data1\">", htmlspecialchars($aggrdata->fields['usename']), "</td>\n</tr>\n";
            echo "<tr>\n\t<th class=\"data left\">{$this->lang['strcomment']}</th>\n";
            echo "\t<td class=\"data1\">", $this->misc->printVal($aggrdata->fields['aggrcomment']), "</td>\n</tr>\n";
            echo "</table>\n";
        } else {
            echo "<p>{$this->lang['strnodata']}</p>\n";
        }

        $navlinks = [
            'showall' => [
                'attr'    => [
                    'href' => [
                        'url'     => 'aggregates',
                        'urlvars' => [
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                        ],
                    ],
                ],
                'content' => $this->lang['straggrshowall'],
            ],
        ];

        if ($data->hasAlterAggregate()) {
            $navlinks['alter'] = [
                'attr'    => [
                    'href' => [
                        'url'     => 'aggregates',
                        'urlvars' => [
                            'action'   => 'alter',
                            'server'   => $_REQUEST['server'],
                            'database' => $_REQUEST['database'],
                            'schema'   => $_REQUEST['schema'],
                            'aggrname' => $_REQUEST['aggrname'],
                            'aggrtype' => $_REQUEST['aggrtype'],
                        ],
                    ],
                ],
                'content' => $this->lang['stralter'],
            ];
        }

        $navlinks['drop'] = [
            'attr'    => [
                'href' => [
                    'url'     => 'aggregates',
                    'urlvars' => [
                        'action'   => 'confirm_drop',
                        'server'   => $_REQUEST['server'],
                        'database' => $_REQUEST['database'],
                        'schema'   => $_REQUEST['schema'],
                        'aggrname' => $_REQUEST['aggrname'],
                        'aggrtype' => $_REQUEST['aggrtype'],
                    ],
                ],
            ],
            'content' => $this->lang['strdrop'],
        ];

        $this->printNavLinks($navlinks, 'aggregates-properties', get_defined_vars());
    }
}
