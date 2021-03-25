<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Interfaces;

use ADOFieldObject;

interface RecordSet
{
    /**
     * Returns the recordCount.
     */
    public function count(): int;

    /**
     * Counts the records in the instance array.
     *
     * @return int number of records in the instance array
     */
    public function RecordCount(): int;

    /**
     * Advance the internal pointer of the instance array
     * if no more fields are left, marks the instance variable $EOF as true.
     */
    public function MoveNext(): void;

    public function FetchField(int $fieldoffset = -1): ADOFieldObject;
}
