<?php

/**
 * PHPPgAdmin v6.0.0-beta.46
 */

// Import the test cases.
require_once 'SecurityTest.php';
require_once 'ExportTest.php';
require_once 'ImportTest.php';

/**
 * This class is to test the whole common manipulation function area.
 * It includes security/export/import manipulation.
 *
 * @coversNothing
 */
class CommonGroupTest extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Common manipulation group test.');
        $this->add(new SecurityTest());
        $this->add(new ExportTest());
        $this->add(new ImportTest());
    }
}
