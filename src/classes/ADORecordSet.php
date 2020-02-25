<?php

/**
 * PHPPgAdmin v6.0.0-RC9
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
    public function count()
    {
        return $this->recordCount();
    }
}
