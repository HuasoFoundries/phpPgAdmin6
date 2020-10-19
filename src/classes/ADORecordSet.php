<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin;

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
     * synonyms RecordCount and RowCount.
     *
     * @return int number of rows or -1 if this is not supported
     */
    public function recordCount()
    {
        return $this->count();
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
