<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin;

use ADOFieldObject;
use Countable;
use PHPPgAdmin\Interfaces\RecordSet;

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
class ArrayRecordSet implements Countable, RecordSet
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

    public function FetchField($off = 0): ADOFieldObject
    {
        // offsets begin at 0

        $o = new ADOFieldObject();

        $o->name = \array_keys($this->fields)[$off] ?? null;
        $value = $this->fields[$o->name ?? \random_bytes(64)] ?? null;
        $o->type = get_debug_type($value);
        $o->max_length = 1024;

        return $o;
    }

    /**
     * Counts the records in the instance array.
     *
     * @return int number of records in the instance array
     */
    public function RecordCount(): int
    {
        return $this->count();
    }

    /**
     * Advance the internal pointer of the instance array
     * if no more fields are left, marks the instance variable $EOF as true.
     */
    public function MoveNext(): void
    {
        $this->fields = \next($this->_array);

        if (false === $this->fields) {
            $this->EOF = true;
        }
    }
}
