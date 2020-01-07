<?php

/**
 * PHPPgAdmin v6.0.0-RC1
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * A test case suite for testing CONVERSION feature in phpPgAdmin, including
 * cases for browsing conversion.
 *
 * @coversNothing
 */
class ConversionTest extends PreconditionSet
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
     * TestCaseID: HBC01
     * Browse the conversions.
     */
    public function testBrowseConversion()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Turn to schema "pg_catalog" page.
        $this->assertTrue(
            $this->get("${webUrl}/redirect", [
                'server'   => $SERVER,
                'section'  => 'schema',
                'database' => 'template1',
                'schema'   => 'pg_catalog', ])
        );
        // Click the "Conversions" hyper link.
        $this->assertTrue($this->clickLink($lang['strconversions']));

        // Verify whether the conversions are displayed.
        // Normally, there should be conversions in this schema, but if there is no,
        // this assert will fail. Need to assert the normal case.
        $this->assertTrue($this->assertText($lang['strsourceencoding']));

        return true;
    }
}
