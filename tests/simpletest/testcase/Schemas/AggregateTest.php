<?php

/**
 * PHPPgAdmin v6.0.0-beta.47
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing AGGREGATE feature in phpPgAdmin, including
 * cases for adding, browsing and dropping aggregates.
 *
 * @coversNothing
 */
class AggregateTest extends PreconditionSet
{
    /**
     * Set up the precondition.
     */
    public function setUp()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        // Login the system.
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
     * TestCaseID: HCA01
     * Creates a new aggregate.
     */
    public function testCreateAggregate()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to "sql" page.
        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database',
                'action'   => 'sql', ])
        );
        // Enter the definition of the new aggregate.
        $this->assertTrue($this->setField('query', 'CREATE AGGREGATE '.
            'complex_sum(sfunc1 = box_intersect, basetype = box,'.
            ' stype1 = box, initcond1 = \'(0,0)\');'));

        // Click the button "Go" to create a new aggregate.
        $this->assertTrue($this->clickSubmit($lang['strgo']));
        // Verify whether the aggregates is created correctly.
        $this->assertTrue($this->assertText($lang['strsqlexecuted']));

        return true;
    }

    /**
     * TestCaseID: HBA01
     * Displays all the aggregates.
     */
    public function testBrowseAggregates()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to "Aggregates" page.
        $this->assertTrue($this->get(
            "${webUrl}/aggregates",
            [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ]
        ));

        // Verify whether the aggregates is displayed correctly.
        $this->assertTrue($this->assertText('complex_sum'));
    }

    /**
     * TestCaseID: HDA01
     * Drop a aggregate.
     */
    public function testDropAggregate()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to "sql" page.
        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database',
                'action'   => 'sql', ])
        );

        $this->assertTrue($this->setField('query', 'DROP AGGREGATE'.
            ' complex_sum(box);'));

        // Click the button "Go" to drop the aggregate.
        $this->assertTrue($this->clickSubmit($lang['strgo']));
        // Verify whether the aggregates is dropped correctly.
        $this->assertTrue($this->assertText($lang['strsqlexecuted']));

        return true;
    }
}
