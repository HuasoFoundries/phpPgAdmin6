<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing INFO feature in phpPgAdmin.
 *
 * @coversNothing
 */
class InfoTest extends PreconditionSet
{
    /**
     * Set up the preconditon.
     */
    public function setUp()
    {
        global $webUrl;
        global $lang;
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
     * TestCaseID: TSI01
     * List the performance info of the parent table -- student.
     */
    public function testListParentTableInfo()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/info", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'subject'  => 'table', ])
        );

        return true;
    }

    /**
     * TestCaseID: TSI02
     * List the performance info of the children table -- college_student.
     */
    public function testListChildrenTableInfo()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/info", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'college_student',
                'subject'  => 'table', ])
        );

        return true;
    }

    /**
     * TestCaseID: TSI03
     * List the performance info of the foreign table -- department.
     */
    public function testListForeignTableInfo()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/info", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'department',
                'subject'  => 'table', ])
        );

        return true;
    }

    /**
     * TestCaseID: TSP01
     * Show the properties of the foreign key constraint.
     */
    public function testShowForeignKeyProperties()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/info?", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'department',
                'subject'  => 'table', ])
        );

        $this->assertTrue($this->clickLink($lang['strproperties']));
        $this->assertText('FOREIGN KEY (dep_id) REFERENCES department(id) '.
            'ON UPDATE RESTRICT ON DELETE RESTRICT');

        return true;
    }
}
