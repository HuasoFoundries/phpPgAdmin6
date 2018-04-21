<?php

/**
 * PHPPgAdmin v6.0.0-beta.42
 */

namespace PHPPgAdmin;

/**
 * @file
 * Really simple RecordSet to allow printTable of arrays.
 *
 * Id: ArrayRecordSet.php,v 1.3 2007/01/10 01:46:28 soranzo Exp $
 *
 * @package PHPPgAdmin
 */

/**
 * Really simple RecordSet to allow printTable of arrays.
 *
 * Id: ArrayRecordSet.php,v 1.3 2007/01/10 01:46:28 soranzo Exp $
 *
 * @package PHPPgAdmin
 */
class ArrayRecordSet
{
    public $_array;
    public $_count;
    public $EOF = false;
    public $fields;

    public function __construct($data)
    {
        $this->_array = $data;
        $this->_count = count($this->_array);
        $this->fields = reset($this->_array);
        if ($this->fields === false) {
            $this->EOF = true;
        }
    }

    public function recordCount()
    {
        return $this->_count;
    }

    public function moveNext()
    {
        $this->fields = next($this->_array);
        if ($this->fields === false) {
            $this->EOF = true;
        }
    }
}
