<?php

/**
 * PHPPgAdmin 6.1.2
 */

namespace PHPPgAdmin;

use Countable;

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
class ArrayRecordSet implements Countable
{
    public $EOF = false;

    public $fields;

    private $_array;

    /**
     * Constructor.
     *
     * @param array $data The input array
     */
    public function __construct($data)
    {
        $this->_array = $data;
        $this->fields = \reset($this->_array);

        if (false === $this->fields) {
            $this->EOF = true;
        }
    }

    /**
     * Returns the recordCount.
     */
    public function count(): int
    {
        return \count($this->_array);
    }

    /**
     * Counts the records in the instance array.
     *
     * @return int number of records in the instance array
     */
    public function recordCount()
    {
        return $this->count();
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
