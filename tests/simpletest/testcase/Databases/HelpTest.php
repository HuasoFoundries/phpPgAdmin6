<?php

/**
 * PHPPgAdmin v6.0.0-beta.46
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the help sub function.
 *
 * @coversNothing
 */
class HelpTest extends PreconditionSet
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
     * TestCaseId:DCD001;
     * This test is used to test help links.
     *
     * Note: It's strange here, because all the links are outside.
     *       So the Pattern cannot be invoked directly.
     */
    public function testHelpWithInnerSchema()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Locate the list page of database.
        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database', ])
        );

        // Click the link about help.
        $this->assertTrue($this->get("${webUrl}/help"));
        $this->assertTrue($this->get("${webUrl}/help?help=pg.schema"));
        $this->assertTrue($this->get("${webUrl}/help?help=pg.column.add"));

        // Comment this for avoiding error by Xdebug.
        // Becase we cannot assert something about the content of the page via
        // hyperlink outside
        // $this->assertWantedPattern('/"Schemas"/');

        return true;
    }

    /**
     * TestCaseId:DCD002;
     * This test is used to test help links from the index links.
     */
    public function testHelpWithInrClk()
    {
        global $webUrl, $SERVER, $DATABASE;

        // Locate the list page of language.
        $this->assertTrue(
            $this->get("${webUrl}/database", [
                'server'   => $SERVER,
                'database' => $DATABASE,
                'subject'  => 'database', ])
        );

        $this->assertTrue($this->get("${webUrl}/help", ['server' => $SERVER]));

        // XXX fail because of the version number in the URL
        $this->assertTrue($this->clickLink( /*'http://www.postgresql.org/docs/8.0/' .*/
            'interactive/sql-expressions.html'.
            '#SQL-SYNTAX-TYPE-CASTS'));

        return true;
    }
}
