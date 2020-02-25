<?php

/**
 * PHPPgAdmin v6.0.0-RC8
 */

namespace PHPPgAdmin;

/**
 * @file
 * Extends ADORecordSet to let correct inference on PHPDoc params
 *
 * @package PHPPgAdmin
 */

/**
 * Extends ADORecordSet to let correct inference on PHPDoc params.
 *
 * @package PHPPgAdmin
 */
class ADORecordSet extends \ADORecordSet implements \Countable
{
    /**
     * Returns the recordCount.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->NumRows();
    }
}
