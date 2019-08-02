<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

// Import necessary library files to setup the testcase.
// And for web testcase, the library web_tester.php shoule be included.

require_once "${PHP_SIMPLETEST_HOME}/test_case.php";
require_once "${PHP_SIMPLETEST_HOME}/web_tester.php";
require_once "${PHP_SIMPLETEST_HOME}/reporter.php";

require_once 'Public/SetPrecondition.php';

require_once 'Server/ServerGroupTest.php';
require_once 'Databases/DatabaseGroupTest.php';
require_once 'Schemas/SchemasGroupTest.php';
require_once 'Tables/TableGroupTest.php';
require_once 'Common/CommonGroupTest.php';

/**
 * This class is to test the whole phpPgAdmin.
 * It includes server/database/schema/table management and common manipulation.
 *
 * @coversNothing
 */
class phpPgAdminGroupTest extends TestSuite
{
    public function __construct()
    {
        parent::__construct('phpPgAdmin automation test.');
        $this->add(new ServerGroupTest());
        //$this->add(new DatabaseGroupTest());
        //$this->add(new SchemasGroupTest());
        //$this->add(new TableGroupTest());
        //$this->add(new CommonGroupTest());
    }
}

$phpPgAdminTest = new phpPgAdminGroupTest();

$phpPgAdminTest->run(new HtmlReporter());
