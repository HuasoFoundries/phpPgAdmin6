<?php

/**
 * PHPPgAdmin v6.0.0-beta.51
 */
require_once 'DatabaseTest.php';
require_once 'SqlTest.php';
require_once 'FindObjectsTest.php';
require_once 'VariablesTest.php';
require_once 'SchemaBasicTest.php';
require_once 'AdminTest.php';
require_once 'ProcessesTest.php';
require_once 'LanguageTest.php';
require_once 'CastsTest.php';
require_once 'HelpTest.php';

/**
 * Run all the test cases as one group.
 *
 * @coversNothing
 */
class DatabaseGroupTest extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Database group test begins.');

        /*
         * Hides it temporary.
         * $this->add(new TableTest());
         */
        $this->add(new SqlTest());
        $this->add(new DatabaseTest());
        $this->add(new FindObjectsTest());
        $this->add(new VariablesTest());
        $this->add(new SchemaBasicTest());
        $this->add(new AdminTest());
        $this->add(new ProcessesTest());
        $this->add(new LanguageTest());
        $this->add(new CastsTest());
        $this->add(new HelpTest());
    }
}
