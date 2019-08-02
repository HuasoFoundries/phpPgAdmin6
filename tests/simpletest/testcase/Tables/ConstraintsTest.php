<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing CONSTRAINT feature in phpPgAdmin, including cases
 * for adding and dropping check, foreign key, unique key and primary key.
 *
 * @coversNothing
 */
class ConstraintsTest extends PreconditionSet
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
        $this->logout();

        return true;
    }

    /**
     * TestCaseID: TAC01
     * Test creating a check constraint in a table.
     */
    public function testAddCheck()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the constraints page
        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server'   => $SERVER,
                'action'   => 'add_check',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student', ])
        );

        // Set properties for the new constraint
        $this->assertTrue($this->setField('name', 'id_check'));
        $this->assertTrue($this->setField('definition', 'id > 0'));

        $this->assertTrue($this->clickSubmit($lang['stradd']));

        // Verify if the constraint is created correctly.
        $this->assertTrue($this->assertText($lang['strcheckadded']));

        return true;
    }

    /**
     * TestCaseID: TDC02
     * Test dropping a check constraint in a table.
     */
    public function testDropCheckKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server'     => $SERVER,
                'action'     => 'confirm_drop',
                'database'   => $DATABASE,
                'schema'     => 'public',
                'table'      => 'student',
                'constraint' => 'id_check',
                'type'       => 'c', ])
        );
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        // Verify if the constraint is dropped correctly.
        $this->assertTrue($this->assertText($lang['strconstraintdropped']));

        return true;
    }

    /**
     * TestCaseID: TAC02
     * Test adding a unique key to a table.
     */
    public function testAddUniqueKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the constraints page
        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server'   => $SERVER,
                'action'   => 'add_unique_key',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student', ])
        );

        // Set properties for the new constraint
        $this->assertTrue($this->setField('name', 'unique_name'));
        $this->assertTrue($this->setField('TableColumnList', ['name']));
        $this->assertTrue($this->setField('tablespace', 'pg_default'));
        $this->assertTrue($this->setField('IndexColumnList[]', 'name'));

        $this->assertTrue($this->clickSubmit($lang['stradd']));
        // Verify if the constraint is created correctly.
        $this->assertTrue($this->assertText($lang['struniqadded']));

        return true;
    }

    /**
     * TestCaseID: TDC01
     * Test dropping a unique constraint in a table.
     */
    public function testDropUniqueKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server'   => $SERVER, 'action'       => 'confirm_drop',
                'database' => $DATABASE, 'schema'     => 'public',
                'table'    => 'student', 'constraint' => 'unique_name', ])
        );

        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        // Verify if the constraint is dropped correctly.
        $this->assertTrue($this->assertText($lang['strconstraintdropped']));

        return true;
    }

    /**
     * TestCaseID: TAC03
     * Test adding a primary key to a table.
     */
    public function testAddPrimaryKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the constraints page
        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server'   => $SERVER, 'action'   => 'add_primary_key',
                'database' => $DATABASE, 'schema' => 'public',
                'table'    => 'college_student', ])
        );

        // Set properties for the new constraint
        $this->assertTrue($this->setField('name', 'primary_id'));
        $this->assertTrue($this->setField('TableColumnList', ['id']));
        $this->assertTrue($this->setField('tablespace', 'pg_default'));
        $this->assertTrue($this->setField('IndexColumnList[]', 'id'));

        $this->assertTrue($this->clickSubmit($lang['stradd']));

        // Verify if the constraint is created correctly.
        $this->assertTrue($this->assertText($lang['strpkadded']));

        return true;
    }

    /**
     * TestCaseID: TDC03
     * Test dropping a primary key constraint in a table.
     */
    public function testDropPrimaryKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Remove the primary key
        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server' => $SERVER, 'action' => 'confirm_drop', 'database'      => $DATABASE,
                'schema' => 'public', 'table' => 'college_student', 'constraint' => 'primary_id',
                'type'   => 'p', ])
        );
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertTrue($this->assertText($lang['strconstraintdropped']));

        return true;
    }

    /**
     * TestCaseID: TAC03
     * Test adding a foreign key to a table.
     */
    public function testAddForeignKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the constraints page
        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server' => $SERVER, 'action' => 'add_foreign_key', 'database' => $DATABASE,
                'schema' => 'public', 'table' => 'student', ])
        );

        // Set properties for the new constraint
        $this->assertTrue($this->setField('name', 'foreign_id'));
        $this->assertTrue($this->setField('TableColumnList', ['id']));
        $this->assertTrue($this->setField('IndexColumnList[]', 'id'));
        $this->assertTrue($this->setField('target', 'department'));

        $this->assertTrue($this->clickSubmit($lang['stradd']));

        $this->assertTrue($this->setField('TableColumnList', ['id']));

        $this->assertTrue($this->setFieldById('IndexColumnList', 'id'));
        $this->assertTrue($this->setField('upd_action', 'RESTRICT'));
        $this->assertTrue($this->setField('del_action', 'RESTRICT'));
        $this->assertTrue($this->clickSubmit($lang['stradd']));

        // Verify if the constraint is created correctly.
        $this->assertTrue($this->assertText($lang['strfkadded']));

        return true;
    }

    /**
     * TestCaseID: TDC04
     * Test dropping a foreign key constraint in a table.
     */
    public function testDropForeignKey()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Remove the foreign key
        $this->assertTrue(
            $this->get("${webUrl}/constraints", [
                'server' => $SERVER, 'action' => 'confirm_drop', 'database' => $DATABASE,
                'schema' => 'public', 'table' => 'student', 'constraint'    => 'foreign_id',
                'type'   => 'f', ])
        );
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertTrue($this->assertText($lang['strconstraintdropped']));

        return true;
    }
}
