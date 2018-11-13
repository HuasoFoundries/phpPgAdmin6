<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

// Import the test cases.
require_once 'UsersTest.php';
require_once 'GroupsTest.php';
require_once 'ReportsTest.php';
require_once 'TableSpacesTest.php';

/**
 * This class is to test the whole server function area.
 * It includes users/groups/reports/tablespaces management.
 *
 * @coversNothing
 */
class ServerGroupTest extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Server management group test.');
        $this->add(new UsersTest());
        //$this->add(new GroupsTest());
        //$this->add(new ReportsTest());
        //$this->add(new TableSpacesTest());
    }
}
