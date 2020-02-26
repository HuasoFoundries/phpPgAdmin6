<?php

// declare(strict_types=1);

/**
 * PHPPgAdmin vv6.0.0-RC8-16-g13de173f
 *
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
}
