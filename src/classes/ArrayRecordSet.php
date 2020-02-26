<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

namespace PHPPgAdmin;

/**
 * @file
 * Really simple RecordSet to allow printTable of arrays.
 *
 * Id: ArrayRecordSet.php,v 1.3 2007/01/10 01:46:28 soranzo Exp $
 */

/**
 * Really simple RecordSet to allow printTable arrays.
 * Mimics the behavior of an ADORecordset.
 *
 * Id: ArrayRecordSet.php,v 1.3 2007/01/10 01:46:28 soranzo Exp $
 */
class ArrayRecordSet
{
    public $EOF = false;

    public $fields;

    private $_array;

    private $_count;

    /**
     * Constructor.
     *
     * @param array $data The input array
     */
    public function __construct($data)
    {
        $this->_array = $data;
        $this->_count = \count($this->_array);
        $this->fields = \reset($this->_array);

        if (false === $this->fields) {
            $this->EOF = true;
        }
    }

    /**
     * Counts the records in the instance array.
     *
     * @return int number of records in the instance array
     */
    public function recordCount()
    {
        return $this->_count;
    }

    /**
     * Advance the internal pointer of the instance array
     * if no more fields are left, marks the instance variable $EOF as true.
     */
    public function moveNext(): void
    {
        $this->fields = \next($this->_array);

        if (false === $this->fields) {
            $this->EOF = true;
        }
    }
}
