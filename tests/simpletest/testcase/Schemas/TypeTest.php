<?php

/**
 * PHPPgAdmin v6.0.0-RC1
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing TYPE feature in phpPgAdmin, including
 * cases for creating, dropping types and showing type's properties.
 *
 * @coversNothing
 */
class TypeTest extends PreconditionSet
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
     * TestCaseID: HCT01
     * Create a type.
     */
    public function testCreateType()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to "Types" page.
        $this->assertTrue(
            $this->get("${webUrl}/types", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ])
        );
        $this->assertTrue($this->clickLink($lang['strcreatetype']));

        // Enter the definition of the type.
        $this->assertTrue($this->setField('typname', 'complex'));
        $this->assertTrue($this->setField('typin', 'abs'));
        $this->assertTrue($this->setField('typout', 'abs'));
        $this->assertTrue($this->setField('typlen', '2'));

        // Click the "Create" button to create a type.
        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        return true;
    }

    /**
     * TestCaseID: HCT02
     * Create a composite type.
     */
    public function testCreateCompositeType()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to "Types" page.
        $this->assertTrue(
            $this->get("${webUrl}/types", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ])
        );
        $this->assertTrue($this->clickLink($lang['strcreatecomptype']));

        // Create without composite type name.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));
        $this->assertTrue($this->assertText($lang['strtypeneedsname']));

        // Enter the name of the new composite type.
        $this->assertTrue($this->setField('name', 'compositetype'));

        // Create without composite type field.
        //$this->assertTrue($this->clickSubmit($lang['strnext']));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        $this->assertTrue($this->clickSubmit('Next >'));
        $this->assertTrue($this->assertText($lang['strtypeneedscols']));

        $this->assertTrue($this->setField('fields', '2'));
        $this->assertTrue($this->setField('typcomment', 'Create in testcase'));
        $this->assertTrue($this->clickSubmit('Next >'));
        // If we do not hardcoded it here, it will cause fail. Encoding issue.
        //$this->assertTrue($this->clickSubmit('Next >'));

        // Create the composite type without the definition of fields.
        $this->assertTrue($this->clickSubmit($lang['strcreate']));
        $this->assertTrue($this->assertText($lang['strtypeneedsfield']));

        // Enter the fields information.
        $this->assertTrue($this->setField('field[0]', 'firstfield'));
        $this->assertTrue($this->setField('type[0]', 'bigint'));
        $this->assertTrue($this->setField('field[1]', 'secondfield'));
        $this->assertTrue($this->setField('type[1]', 'bigint'));

        // Click the "Create" button to create the composite type.
        $this->assertTrue($this->clickSubmit($lang['strcreate']));
        // Verify if the type create correctly.
        $this->assertTrue($this->assertText($lang['strtypecreated']));

        return true;
    }

    /**
     * TestCaseID: HTP01
     * Show the properties of the specified type.
     */
    public function testShowProperty()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to "Types" page.
        $this->assertTrue(
            $this->get("${webUrl}/types", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'pg_catalog',
                'subject'  => 'schema', ])
        );

        // Show the properties of general type.
        $this->assertTrue($this->clickLink('integer'));
        // Verify whether the properties are displayed correctly.
        $this->assertTrue($this->assertText('int4'));

        // Turn to "Types" page.
        $this->assertTrue(
            $this->get("${webUrl}/types", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'schema'   => 'public',
                'subject'  => 'schema', ])
        );

        // Show the properties of a composite type "compositetype".
        $this->assertTrue($this->clickLink('compositetype'));
        // Verify whether the properties are displayed correctly.
        $this->assertTrue($this->assertText('firstfield'));

        return true;
    }

    /**
     * TestCaseID: HDT01
     * Drop the type.
     */
    public function testDropType()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to type-dropped confirm page.
        $this->assertTrue(
            $this->get("${webUrl}/types", [
                'server'   => $SERVER,
                'action'   => 'confirm_drop',
                'database' => $DATABASE,
                'schema'   => 'public',
                'type'     => 'compositetype', ])
        );

        $this->assertTrue($this->setField('cascade', true));

        // Click the "Drop" button to drop the type.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        // Verify whether the type is dropped correctly.
        $this->assertTrue($this->assertText($lang['strtypedropped']));

        return true;
    }
}
