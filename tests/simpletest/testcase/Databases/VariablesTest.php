<?php

/**
 * PHPPgAdmin v6.0.0-RC1
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the Variables about phpPgAdmin implementation.
 *
 * @coversNothing
 */
class VariablesTest extends PreconditionSet
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
     * Release the relational resource.
     */
    public function tearDown()
    {
        // Logout this system.
        $this->logout();

        return true;
    }

    /**
     * TestCaseId: DVA001
     * This test is used to display the list of Prcesses.
     */
    public function testVariablesList()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database',
                'action'   => 'variables', ])
        );

        $this->assertText($lang['strname']);
        $this->assertText($lang['strsetting']);

        return true;
    }
}
