<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin;

/**
 * @file
 * Extends ADORecordSet to let correct inference on PHPDoc params
 */

/**
 * Extends ADORecordSet to let correct inference on PHPDoc params.
 */
class ADORecordSet extends \ADORecordSet implements \Countable
{
    /**
     * Returns the recordCount.
     */
    public function count(): int
    {
        return $this->NumRows();
    }

    /**
     * Returns the recordCount.
     *
     * @param int $fieldoffset
     *
     * @return \ADOFieldObject the field
     */
    public function fetchField($fieldoffset = -1): \ADOFieldObject
    {
        return parent::fetchField();
    }
}
