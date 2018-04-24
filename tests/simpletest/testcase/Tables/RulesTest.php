<?php

/**
 * PHPPgAdmin v6.0.0-beta.44
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing RULE feature in phpPgAdmin.
 *
 * @coversNothing
 */
class RulesTest extends PreconditionSet
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
     * TestCaseID: TCR01
     * Create a rule for a table.
     */
    public function testCreateRule()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/rules", [
                'server'   => $SERVER,
                'action'   => 'create_rule',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'subject'  => 'table'])
        );

        // Set properties for the new rule
        $this->assertTrue($this->setField('name', 'insert_stu_rule'));
        $this->assertTrue($this->setField('event', 'INSERT'));
        $this->assertTrue($this->setField('where', ''));
        $this->assertTrue($this->setField('type', 'NOTHING'));
        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        // Verify if the rule is created correctly.
        $this->assertTrue($this->assertText($lang['strrulecreated']));

        return true;
    }

    /**
     * TestCaseID: TCR02
     * Cancel creating a rule for a table.
     */
    public function testCancelCreateRule()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/rules", [
                'server'   => $SERVER,
                'action'   => 'create_rule',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'subject'  => 'table'])
        );

        // Set properties for the new rule
        $this->assertTrue($this->setField('name', 'insert_stu_rule'));
        $this->assertTrue($this->setField('event', 'INSERT'));
        $this->assertTrue($this->setField('where', ''));
        $this->assertTrue($this->setField('instead', true));
        $this->assertTrue($this->clickSubmit($lang['strcancel']));

        return true;
    }

    /**
     * TestCaseID: TDR03
     * Cancel the drop rule operation.
     */
    public function testCancelDropRule()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Drop the rule
        $this->assertTrue(
            $this->get("${webUrl}/rules", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'reltype'  => 'table',
                'table'    => 'student',
                'subject'  => 'rule',
                'rule'     => 'insert_stu_rule'])
        );
        $this->assertTrue($this->clickSubmit($lang['strno']));

        return true;
    }

    /**
     * TestCaseID: TDR01
     * Drop a rule from the table.
     */
    public function testDropRule()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Drop the rule
        $this->assertTrue(
            $this->get("${webUrl}/rules", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'reltype'  => 'table',
                'table'    => 'student',
                'subject'  => 'rule',
                'rule'     => 'insert_stu_rule'])
        );
        $this->assertTrue($this->clickSubmit($lang['stryes']));
        // Verify if the rule is dropped correctly.
        $this->assertTrue($this->assertText($lang['strruledropped']));

        return true;
    }

    /**
     * TestCaseID: TDR02
     * Drop a rule from the table witch CASCADE checked.
     */
    public function testDropRuleWithCascade()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;
        global $POWER_USER_NAME;
        global $POWER_USER_PASSWORD;

        // Go to the Rules page
        $this->assertTrue(
            $this->get("${webUrl}/rules", [
                'server'   => $SERVER,
                'action'   => 'create_rule',
                'database' => $DATABASE,
                'schema'   => 'public',
                'table'    => 'student',
                'subject'  => 'table'])
        );

        // Set properties for the new rule
        $this->assertTrue($this->setField('name', 'insert_stu_rule'));
        $this->assertTrue($this->setField('event', 'INSERT'));
        $this->assertTrue($this->setField('where', ''));
        $this->assertTrue($this->setField('type', 'SOMETHING'));
        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        // Verify if the rule is created correctly.
        $this->assertTrue($this->assertText($lang['strrulecreated']));

        $this->logout();
        $this->login(
            $POWER_USER_NAME,
            $POWER_USER_PASSWORD,
            "${webUrl}/login"
        );

        // Drop the rule
        $this->assertTrue(
            $this->get("${webUrl}/rules", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'reltype'  => 'table',
                'table'    => 'student',
                'subject'  => 'rule',
                'rule'     => 'insert_stu_rule'])
        );
        $this->assertTrue($this->setField('cascade', true));
        $this->assertTrue($this->clickSubmit($lang['stryes']));
        // Verify if the rule is dropped correctly.
        $this->assertTrue($this->assertText($lang['strruledropped']));

        return true;
    }
}
