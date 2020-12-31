<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin;

use ADOFieldObject;
use ADORecordSet as ADODBRecordsetClass;
use Countable;

/**
 * Extends ADORecordSet to let correct inference on PHPDoc params.
 */
class ADORecordSet extends ADODBRecordsetClass implements Countable, Interfaces\RecordSet
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
    public function RecordCount()
    {
        return $this->count();
    }

    /**
     * Returns the recordCount.
     *
     * @param int $fieldoffset
     *
     * @return ADOFieldObject the field
     */
    public function FetchField($fieldoffset = -1): ADOFieldObject
    {
        return parent::FetchField();
    }

    public function MoveNext():void {
parent::MoveNext();
    }
}
