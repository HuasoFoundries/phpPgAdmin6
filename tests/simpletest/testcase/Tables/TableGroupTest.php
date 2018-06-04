<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */
require_once 'ColumnTest.php';
require_once 'IndexesTest.php';
require_once 'ConstraintsTest.php';
require_once 'TriggersTest.php';
require_once 'RulesTest.php';
require_once 'InfoTest.php';
require_once 'DeadlockTest.php';

/**
 *  Group test class to run all test cases in the table function area automatically.
 *
 * @coversNothing
 */
class TableGroupTest extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Table management group test.');
        $this->add(new ColumnTest());
        $this->add(new TriggersTest());
        $this->add(new RulesTest());
        $this->add(new IndexesTest());
        $this->add(new InfoTest());
        $this->add(new ConstraintsTest());
        $this->add(new DeadlockTest());
    }
}
