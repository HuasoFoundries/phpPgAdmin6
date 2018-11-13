<?php

/**
 * PHPPgAdmin v6.0.0-beta.50
 */

// Import the precondition class.
if (is_dir('../Public')) {
    require_once '../Public/SetPrecondition.php';
}

/**
 * This class is to test the report management.
 * It includes create/drop/edit/list/run reports.
 *
 * @coversNothing
 */
class ReportsTest extends PreconditionSet
{
    // Declare the member variable for report name.
    private $_reportName = 'testReport';

    public function setUp()
    {
        global $webUrl;
        global $SUPER_USER_NAME;
        global $SUPER_USER_PASSWORD;

        $this->login($SUPER_USER_NAME, $SUPER_USER_PASSWORD, "${webUrl}/login");

        return true;
    }

    public function tearDown()
    {
        $this->logout();

        return true;
    }

    /*
     * TestCaseID: SCR01
     * Test to create report.
     */
    public function testCreate()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the create report page.
        $this->assertTrue($this->get("${webUrl}/reports", ['server' => $SERVER]));
        $this->assertTrue($this->clickLink($lang['strcreatereport']));

        // Enter information for creating a report.
        $this->assertTrue($this->setField('report_name', $this->_reportName));
        $this->assertTrue($this->setField('db_name', $DATABASE));
        $this->assertTrue($this->setField('descr', 'comment'));
        $this->assertTrue($this->setField('report_sql', 'select * from student where 1=0'));

        //Then submit and verify it.
        $this->assertTrue($this->clickSubmit($lang['strsave']));
        $this->assertText($lang['strreportcreated']);
        $this->assertText($this->_reportName);

        return true;
    }

    /*
     * TestCaseID: SRR01
     * Test to run existing report.
     */
    public function testRun()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Run the existing report and verify it.
        $this->assertTrue($this->get("${webUrl}/reports", ['server' => $SERVER]));
        $this->assertTrue($this->clickLink($lang['strexecute']));
        $this->assertText($lang['strnodata']);

        /* XXX there's no refresh link on report results page. see sql.php
        $this->assertTrue($this->clickLink($lang['strrefresh']));
        $this->assertText($lang['strnodata']);
         */
        /* XXX there's no expand-collapse link on report results page. see sql.php
        $this->assertTrue($this->clickLink($lang['strexpand']));
        $this->assertText($lang['strnodata']);
        $this->assertText($lang['strcollapse']);

        $this->assertTrue($this->clickLink($lang['strcollapse']));
        $this->assertText($lang['strnodata']);
        $this->assertText($lang['strexpand']);
         */

        /* XXX btw, there's a "create report" link in the report results page o_O */

        return true;
    }

    /*
     * TestCaseID: SER01
     * Test to edit existing report.
     */
    public function testEdit()
    {
        global $webUrl;
        global $lang, $SERVER, $DATABASE;

        // Turn to the edit report page.
        $this->assertTrue($this->get("${webUrl}/reports", ['server' => $SERVER]));
        $this->assertTrue($this->clickLink($this->_reportName));
        $this->assertTrue($this->clickLink($lang['stredit']));

        // Enter the information for altering the report's properties.
        $this->assertTrue($this->setField('report_name', $this->_reportName));
        $this->assertTrue($this->setField('db_name', $DATABASE));
        $this->assertTrue($this->setField('descr', 'comment is changed'));
        $this->assertTrue($this->setField('report_sql', 'select * from student where 0=1'));

        // Then submit and verify it.
        $this->assertTrue($this->clickSubmit($lang['strsave']));
        $this->assertText($lang['strreportcreated']);
        $this->assertText($this->_reportName);

        return true;
    }

    /*
     * TestCaseID: SDR01
     * Test to drop existing report.
     */
    public function testDrop()
    {
        global $webUrl;
        global $lang, $SERVER;

        // Turn to the drop report page.
        $this->assertTrue($this->get("${webUrl}/reports", ['server' => $SERVER]));
        $this->assertTrue($this->clickLink($lang['strdrop']));

        // Confirm to drop the report and verify it.
        $this->assertTrue($this->clickSubmit($lang['strdrop']));
        $this->assertText($lang['strreportdropped']);

        return true;
    }
}
