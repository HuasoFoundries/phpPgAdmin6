<?php

/**
 * PHPPgAdmin v6.0.0-beta.46
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the Processes about PostgreSql implementation.
 *
 * @coversNothing
 */
class ProcessesTest extends PreconditionSet
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
     * TestCaseId: DPS001
     * This test is used to test Processes.
     *
     * Note: This sub function is dynamic during the run time.
     */
    public function testProcesses()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database',
                'action'   => 'processes', ])
        );

        $this->assertText($lang['strnodata']);

        return true;
    }
}
