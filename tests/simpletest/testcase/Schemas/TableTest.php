<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing TABLE feature in phpPgAdmin, including
 * cases for testing table DDL and DML operations.
 *
 * @coversNothing
 */
class TableTest extends PreconditionSet
{
    /**
     * Set up the preconditon.
     */
    public function setUp()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        $this->login(
            $SUPER_USER_NAME,

            $SUPER_USER_PASSWORD,
            "${webUrl}/login"
        );

        return true;
    }

    /**
     * Clean up all the result.
     */
    public function tearDown()
    {
        // Logout from the system.
        $this->logout();

        return true;
    }

    /**
     * TestCaseID: HCT01
     * This testcase is used to create a table in an existing database.
     */
    public function testCreateTable()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the create table page to create a table.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'create',
                'database' => $DATABASE,
                'schema'   => 'public', ])
        );

        // Enter the table name and field number.
        $this->assertTrue($this->setField('name', 'newtable'));
        $this->assertTrue($this->setField('fields', '2'));
        $this->assertTrue($this->setField('spcname', 'pg_default'));
        $this->assertTrue($this->setField('tblcomment', 'Create from SimpleTest!'));

        // Click the button "next >" for entering the detail information.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));

        // Enter the detail information of the table.
        $this->assertTrue($this->setField('field[0]', 'firstfield'));
        $this->assertTrue($this->setField('type[0]', 'text'));
        $this->assertTrue($this->setField('array[0]', ''));

        $this->assertTrue($this->setField('field[1]', 'secondfield'));
        $this->assertTrue($this->setField('type[1]', 'text'));
        $this->assertTrue($this->setField('array[1]', ''));

        // Click the button "Create" for creating the table
        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        // Verify whether the table is created correctly.
        $this->assertTrue($this->assertText($lang['strtablecreated']));

        // Drop the table which is created in the testcase.
        $this->dropTable($DATABASE, 'newtable', 'public');

        return true;
    }

    /**
     * TestCaseID: HCT02
     * Create a table with the wrong field number.
     */
    public function testCreateTableWithBadFieldNumber()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the create table page to create a table.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'create',
                'database' => $DATABASE,
                'schema'   => 'public', ])
        );

        // Enter no name.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));
        $this->assertTrue($this->assertText($lang['strtableneedsname']));

        // Enter the table name and field number.
        $this->assertTrue($this->setField('name', 'badtable'));

        // Enter no name.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));
        $this->assertTrue($this->assertText($lang['strtableneedscols']));

        // Enter illegal field number.
        $this->assertTrue($this->setField('fields', 'illegalnumber'));
        $this->assertTrue($this->setField('tblcomment', 'Wrong field number.'));

        // Click the button "next >" for entering the detail information.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));

        //Verify whether the table creation fialed.
        $this->assertTrue($this->assertText($lang['strtableneedscols']));

        return true;
    }

    /**
     *  TestCaseID: HCT03
     * Create a table with the wrong field information.
     */
    public function testCreateTableWithBadFieldData()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the create table page to create a table.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'create',
                'database' => $DATABASE,
                'schema'   => 'public', ])
        );

        // Enter the table name and field number.
        $this->assertTrue($this->setField('name', 'badfield'));
        $this->assertTrue($this->setField('fields', '2'));
        $this->assertTrue($this->setField('spcname', 'pg_default'));
        $this->assertTrue($this->setField('tblcomment', 'With illegal field information!'));

        // Click the button "next >" for entering the detail information.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));

        // Enter the detail information of the table.
        $this->assertTrue($this->setField('field[0]', 'field1'));
        $this->assertTrue($this->setField('type[0]', 'integer'));
        $this->assertTrue($this->setField('array[0]', '[ ]'));
        $this->assertTrue($this->setField('default[0]', '100'));

        $this->assertTrue($this->setField('field[1]', 'field2'));
        $this->assertTrue($this->setField('type[1]', 'integer'));
        $this->assertTrue($this->setField('array[1]', '[ ]'));
        $this->assertTrue($this->setField('default[0]', 'testcase'));

        // Click the button "Create" for creating the table
        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        //Verify whether the table creation failed.
        $this->assertTrue($this->assertText($lang['strtableneedsfield']));

        return true;
    }

    /**
     * TestCaseID: HIT01
     * Insert a row into an existing table.
     */
    public function testInsertOneRow()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Create a table.
        $this->createTable($DATABASE, 'public', 'viewtest', '3');

        // Turn to the "Insert row" interface.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confinsertrow',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Set the value of the fields.
        $this->assertTrue($this->setField('values[field0]', 'row1column1'));
        $this->assertTrue($this->setField('values[field1]', 'row1column2'));
        $this->assertTrue($this->setField('values[field2]', 'row1column3'));

        // Click the "Insert" button to insert a row.
        $this->assertTrue($this->clickSubmit($lang['strinsert']));
        // Verify whether the row is inserted successfully.
        $this->assertTrue($this->assertText($lang['strrowinserted']));

        return true;
    }

    /**
     * TestCaseID: HIT02
     * Insert two rows into an existing table.
     */
    public function testInsertTwoRows()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Insert row" interface.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confinsertrow',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Set the value of the fields.
        $this->assertTrue($this->setField('values[field0]', 'row2column1'));
        $this->assertTrue($this->setField('values[field1]', 'row2column2'));
        $this->assertTrue($this->setField('values[field2]', 'row2column3'));

        // Click the "Insert & Repeat" button to insert a row.
        //$this->assertTrue($this->clickSubmit($lang['strinsertandrepeat']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Insert & Repeat'));
        // Verify whether the row is inserted successfully.
        $this->assertTrue($this->assertText($lang['strrowinserted']));

        // Set the value of the fields again.
        $this->assertTrue($this->setField('values[field0]', 'row3column1'));
        $this->assertTrue($this->setField('values[field1]', 'row3column2'));
        $this->assertTrue($this->setField('values[field2]', 'row3column3'));

        // Click the "Insert" button to insert a row.
        $this->assertTrue($this->clickSubmit($lang['strinsert']));
        // Verify whether the row is inserted successfully.
        $this->assertTrue($this->assertText($lang['strrowinserted']));

        return true;
    }

    /**
     * TestCaseID: HIT03
     * Insert one row with illegal data type into an existing table.
     */
    public function testInsertWithBadData()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Insert row" interface.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confinsertrow',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Set the value of the fields.
        $this->assertTrue($this->setField('format[field0]', 'Expression'));
        $this->assertTrue($this->setField('format[field1]', 'Expression'));
        $this->assertTrue($this->setField('format[field2]', 'Expression'));

        $this->assertTrue($this->setField('values[field0]', 'row0column1'));
        $this->assertTrue($this->setField('values[field1]', 'row0column2'));
        $this->assertTrue($this->setField('values[field2]', 'row0column3'));

        // Click the "Insert" button to insert a row.
        $this->assertTrue($this->clickSubmit($lang['strinsert']));
        // Verify whether the row insertion failed.
        $this->assertTrue($this->assertText($lang['strrowinsertedbad']));

        return true;
    }

    /**
     * TestCaseID: HER01
     * Edit a row.
     * XXX Fail cause we have no index on viewtest, created by $this->createable
     * see Public/SetPrecondition.php.
     */
    public function testEditRow()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Tables" interface.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public', ])
        );
        // Select the table "viewtest".
        $this->assertTrue($this->clickLink('viewtest'));
        // Browse the table.
        $this->assertTrue($this->clickLink($lang['strbrowse']));
        // Select a row.
        $this->assertTrue($this->clickLink($lang['stredit']));

        // Edit the row.
        $this->assertTrue($this->setField('values[field0]', 'updatecolumn0'));
        $this->assertTrue($this->setField('values[field1]', 'updatecolumn1'));
        $this->assertTrue($this->setField('values[field2]', 'updatecolumn2'));

        // Click the "Save" button and save the edits.
        $this->assertTrue($this->clickSubmit($lang['strsave']));
        // Verify whether the edit is done successfully.
        $this->assertTrue($this->assertText('updatecolumn0'));

        return true;
    }

    /**
     * TestCaseID: HDR01
     * Delete a row.
     * XXX Fail, see comment on testEditRow.
     */
    public function testDeleteRow()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Tables" interface.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public', ])
        );
        // Select the table "viewtest".
        $this->assertTrue($this->clickLink('viewtest'));
        // Browse the table.
        $this->assertTrue($this->clickLink($lang['strbrowse']));
        // Delete a row.
        $this->assertTrue($this->clickLink($lang['strdelete']));

        // Click the "Yes" button and delete the edits.
        $this->assertTrue($this->clickSubmit($lang['stryes']));

        return true;
    }

    /**
     * TestCaseID: HBT01
     * Browse an existing table.
     */
    public function testBrowseTable()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Browse table" interface.
        $this->assertTrue(
            $this->get("${webUrl}/display", [
                'server'      => $SERVER,
                'database'    => $DATABASE,
                'schema'      => 'public',
                'subject'     => 'table',
                'return_url'  => 'tables.php%3Fdatabase%3Dtest%26amp%3Bschema%3Dpublic',
                'return_desc' => 'Back',
                'table'       => 'viewtest', ])
        );

        // Verify whether the rows are displayed.
        $this->assertTrue($this->assertText($lang['strrows']));

        // Click the links in the display page.
        $this->assertTrue($this->clickLink('field0'));
        $this->assertTrue($this->clickLink('field1'));
        $this->assertTrue($this->clickLink($lang['strexpand']));
        $this->assertTrue($this->clickLink($lang['strcollapse']));
        $this->assertTrue($this->clickLink($lang['strrefresh']));
        $this->assertTrue($this->clickLink($lang['strback']));

        return true;
    }

    /**
     * TestCaseID: HST01
     * Select all the rows of the table.
     */
    public function testSelectAll()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "tables" page.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ])
        );
        $this->assertTrue($this->clickLink('viewtest'));

        // Select all the rows.
        $this->assertTrue($this->clickLink($lang['strselect']));
        $this->assertTrue($this->setField('show[field0]', true));
        $this->assertTrue($this->setField('show[field1]', true));
        $this->assertTrue($this->setField('show[field2]', true));

        // Display all the rows.
        $this->assertTrue($this->clickSubmit($lang['strselect']));
        // Verify whether select successful.
        $this->assertTrue($this->assertText('row'));

        return true;
    }

    /**
     * TestCaseID: HST02
     * Select rows according to the query conditions.
     */
    public function testSelectByConditions()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "tables" page.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confselectrows',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Display all columns.
        $this->assertTrue($this->setField('show[field0]', true));
        $this->assertTrue($this->setField('show[field1]', true));
        $this->assertTrue($this->setField('show[field2]', true));
        // Enter the query conditions.
        $this->assertTrue($this->setField('values[field0]', 'row2column1'));
        $this->assertTrue($this->setField('values[field1]', 'row2column2'));
        $this->assertTrue($this->setField('values[field2]', 'row2column3'));

        // Click the "Select" button.
        $this->assertTrue($this->clickSubmit($lang['strselect']));
        // Verify whether select successful.
        $this->assertTrue($this->assertText('row'));

        return true;
    }

    /**
     * TestCaseID: HST03
     * Select data from an existing table with no row display.
     */
    public function testSelectTableNoRowDisplay()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "tables" page.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confselectrows',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Enter the query conditions.
        $this->assertTrue($this->setField('values[field0]', 'row2column1'));
        $this->assertTrue($this->setField('values[field1]', 'row2column2'));
        $this->assertTrue($this->setField('values[field2]', 'row2column3'));

        // Click the "Select" button.
        $this->assertTrue($this->clickSubmit($lang['strselect']));
        // Verify whether select successful.
        $this->assertTrue($this->assertText($lang['strselectneedscol']));

        return true;
    }

    /**
     * TestCaseID: HVT01
     * Vacuum an existing table with the check box "Full" and "Analyze" unchecked.
     */
    public function testVacuumUnchecked()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Vacuum" page.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confirm_vacuum',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Click the "Vacuum" button.
        $this->assertTrue($this->clickSubmit($lang['strvacuum']));
        // Verify whether vacuum successfully.
        $this->assertTrue($this->assertText($lang['strvacuumgood']));

        return true;
    }

    /**
     * TestCaseID: HVT02
     * Vacuum an existing table with the check box "Full" and "Analyze" checked.
     */
    public function testVacuumChecked()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "Vacuum" page.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confirm_vacuum',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        // Make sure the check box "Full" and "Analyze" are checked
        $this->assertTrue($this->setField('vacuum_full', true));
        $this->assertTrue($this->setField('vacuum_analyze', true));

        // Click the "Vacuum" button.
        $this->assertTrue($this->clickSubmit($lang['strvacuum']));
        // Verify whether vacuum successfully.
        $this->assertTrue($this->assertText($lang['strvacuumgood']));

        return true;
    }

    /**
     * TestCaseID: HET01
     * Empty an existing table.
     */
    public function testEmptyTable()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the "tables" page.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ])
        );

        // Empty a table.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confirm_empty',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );
        // Click the "Empty" button to clean the content of the table.
        $this->assertTrue($this->clickSubmit($lang['strempty']));

        // Verify whether the table is emptied successfully.
        $this->assertTrue($this->assertText($lang['strtableemptied']));

        return true;
    }

    /**
     * TestCaseID: HAT01
     * Alter the properties of an existing table.
     */
    public function testAlterTable()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Drop the table.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ])
        );
        // Select the table.
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'confirm_alter',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'viewtest', ])
        );

        $this->assertTrue($this->setField('name', 'testview'));
        $this->assertTrue($this->setField('owner', 'tester'));
        $this->assertTrue($this->setField('tablespace', 'pg_default'));

        $this->assertTrue($this->clickSubmit($lang['stralter']));
        $this->assertTrue($this->assertText($lang['strtablealtered']));

        return true;
    }

    /**
     * TestCaseID: HDT01
     * Drop an existing table.
     */
    public function testDropTable()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Drop the table.
        $this->assertTrue(
            $this->get("${webUrl}/tables", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'testview', ])
        );

        $this->assertTrue($this->setField('cascade', true));
        // Click the "Drop" button to drop the table.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));

        // Verify whether the table is dropped successfully.
        $this->assertTrue($this->assertText($lang['strtabledropped']));

        return true;
    }
}
