<?php

/**
 * PHPPgAdmin v6.0.0-beta.46
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the Schema Basic Management of
 * PostgreSql implementation.
 *
 * @coversNothing
 */
class SchemaBasicTest extends PreconditionSet
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
     * TestCaseId: DCS001
     * This test is used to create one new schema for super user.
     */
    public function testCreateBasSchema()
    {
        global $webUrl, $SUPER_USER_NAME;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database', ])
        );
        $this->assertTrue(
            $this->get("${webUrl}/schemas", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'action'   => 'create', ])
        );

        $this->assertTrue($this->setField('formName', 'testSchemaName'));
        $this->assertTrue($this->setField('formAuth', $SUPER_USER_NAME));
        $this->assertTrue($this->setField(
            'formComment',
            'Comment of test schema.'
        ));

        $this->assertTrue($this->clickSubmit($lang['strcreate']));

        $this->assertText($lang['strschemacreated']);

        return true;
    }

    /**
     * TestCaseId: DAS001
     * This test is used to modify one existent schema for super user.
     */
    public function testAlterBasSchema()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database', ])
        );
        $this->assertTrue(
            $this->get("${webUrl}/redirect", [
                'server'   => $SERVER,
                'section'  => 'database',
                'database' => $DATABASE, ])
        );
        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database', ])
        );
        $this->assertTrue(
            $this->get("${webUrl}/schemas", [
                'server'   => $SERVER,
                'action'   => 'alter',
                'database' => $DATABASE,
                'schema'   => 'testSchemaName', ])
        );

        $this->assertTrue($this->setField(
            'comment',
            'The comment has been changed.'
        ));
        $this->assertTrue($this->clickSubmit('Alter'));

        $this->assertText($lang['strschemaaltered']);

        return true;
    }

    /**
     * TestCaseId: DDS001
     * This test is used to drop one existent schema for super user.
     */
    public function testDropBasSchema()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database', ])
        );
        $this->assertTrue(
            $this->get("${webUrl}/redirect", [
                'server'   => $SERVER,
                'section'  => 'database',
                'database' => $DATABASE, ])
        );
        $this->assertTrue(
            $this->get("${webUrl}/schemas", [
                'server'   => $SERVER,
                'action'   => 'drop',
                'database' => $DATABASE,
                'schema'   => 'testSchemaName', ])
        );

        $this->assertTrue($this->setField('cascade', true));
        $this->assertTrue($this->clickSubmit($lang['strdrop']));

        $this->assertText($lang['strschemadropped']);

        return true;
    }
}
