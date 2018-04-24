<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing COLUMN feature in phpPgAdmin, including
 * cases for adding, altering and dropping columns in a table.
 *
 * @coversNothing
 */
class ColumnTest extends PreconditionSet
{
    /**
     * Set up the preconditon.
     */
    public function setUp()
    {
        global $webUrl;
        global $POWER_USER_NAME;
        global $POWER_USER_PASSWORD;

        $this->login(
            $POWER_USER_NAME,

            $POWER_USER_PASSWORD,
            "${webUrl}/login"
        );

        return true;
    }

    /**
     * Clean up all the result.
     */
    public function tearDown()
    {
        $this->logout();

        return true;
    }

    /**
     * TestCaseID: TNC01
     * Add a column to the table.
     */
    public function testAddColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Columns page
        $this->assertTrue(
            $this->get(
                "${webUrl}/tblproperties",
                ['action'      => 'add_column',
                    'database' => $DATABASE,
                    'schema'   => 'public',
                    'table'    => 'student',
                    'server'   => $SERVER]
            )
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'integer'));
        $this->assertTrue($this->clickSubmit($lang['stradd']));

        // Verify if the column is created correctly.
        $this->assertTrue($this->assertText($lang['strcolumnadded']));

        return true;
    }

    /**
     * TestCaseID: TNC02
     * Add a column with the same name as the existing one.
     */
    public function testAddColumnWithExistingName()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Columns page
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'add_column',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student'])
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'integer'));
        $this->assertTrue($this->clickSubmit($lang['stradd']));

        // Make sure the operation failed
        $this->assertTrue($this->assertText($lang['strcolumnaddedbad']));

        return true;
    }

    /**
     * TestCaseID: TNC03
     * Cancel the add column operation.
     */
    public function testCancelAddColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Columns page
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'add_column',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student'])
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'integer'));
        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TAC01
     * Alter a column of the table.
     */
    public function testAlterColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Columns page
        $this->assertTrue(
            $this->get("${webUrl}/colproperties", [
                'server'   => $SERVER,
                'action'   => 'properties',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'column'   => 'sid'])
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'character'));
        $this->assertTrue($this->setField('length', '18'));
        $this->assertTrue($this->clickSubmit($lang['stralter']));

        // Verify if the column is altered correctly.
        $this->assertTrue($this->assertText($lang['strcolumnaltered']));

        return true;
    }

    /**
     * TestCaseID: TAC02
     * Alter a column to be of negative length.
     */
    public function testNegativeLengthColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Columns page
        $this->assertTrue(
            $this->get("${webUrl}/colproperties", [
                'server'   => $SERVER,
                'action'   => 'properties',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'column'   => 'sid'])
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'character'));
        $this->assertTrue($this->setField('length', '-2'));
        $this->assertTrue($this->clickSubmit($lang['stralter']));

        // Make sure the alteration failed.
        $this->assertTrue($this->assertText($lang['strcolumnalteredbad']));

        return true;
    }

    /**
     * TestCaseID: TAC03
     * Cancel the alter column operation.
     */
    public function testCancelAlterColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Columns page
        $this->assertTrue(
            $this->get("${webUrl}/colproperties", [
                'server'   => $SERVER,
                'action'   => 'properties',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'column'   => 'sid'])
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'character'));
        $this->assertTrue($this->setField('length', '18'));
        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TDC03
     * Cancel the drop column operation.
     */
    public function testCancelDropColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Drop the column
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'column'   => 'sid'])
        );
        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TDC01
     * Drop a column from the table.
     */
    public function testDropColumn()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Drop the column
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'column'   => 'sid'])
        );
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        // Verify if the column is dropped correctly.
        $this->assertTrue($this->assertText($lang['strcolumndropped']));

        return true;
    }

    /**
     * TestCaseID: TDC02
     * Drop a column wich "CASCADE" checked.
     */
    public function testDropColumnWithCascade()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;
        global $POWER_USER_NAME;
        global $POWER_USER_PASSWORD;

        // Go to the Columns page
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'add_column',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student'])
        );

        // Set properties for the new column
        $this->assertTrue($this->setField('field', 'sid'));
        $this->assertTrue($this->setField('type', 'integer'));
        $this->assertTrue($this->clickSubmit($lang['stradd']));

        // Verify if the column is created correctly.
        $this->assertTrue($this->assertText($lang['strcolumnadded']));

        $this->logout();
        $this->login(
            $POWER_USER_NAME,

            $POWER_USER_PASSWORD,
            "${webUrl}/login"
        );

        // Drop the column with CASCADE checked
        $this->assertTrue(
            $this->get("${webUrl}/tblproperties", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'column'   => 'sid'])
        );
        $this->assertTrue($this->setField('cascade', true));
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        // Verify if the column is dropped correctly.
        $this->assertTrue($this->assertText($lang['strcolumndropped']));

        return true;
    }
}
