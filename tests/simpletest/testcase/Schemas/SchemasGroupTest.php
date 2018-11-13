<?php

/**
 * PHPPgAdmin v6.0.0-beta.49
 */

// Import the test case files.
require_once 'TableTest.php';
require_once 'ViewTest.php';
require_once 'SequenceTest.php';
require_once 'FunctionTest.php';
require_once 'DomainTest.php';
require_once 'AggregateTest.php';
require_once 'TypeTest.php';
require_once 'OperatorTest.php';
require_once 'OpClassTest.php';
require_once 'ConversionTest.php';

/**
 *  Group test class to run all test cases in the schema function area automatically.
 *
 * @coversNothing
 */
class SchemasGroupTest extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Schema management group test.');

        $this->add(new TableTest());
        $this->add(new ViewTest());
        $this->add(new SequenceTest());
        $this->add(new FunctionTest());
        $this->add(new TypeTest());
        $this->add(new DomainTest());
        $this->add(new AggregateTest());
        $this->add(new OperatorTest());
        $this->add(new OpClassTest());
        $this->add(new ConversionTest());
    }
}
