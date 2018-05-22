<?php

/**
 * PHPPgAdmin v6.0.0-beta.43
 */

namespace PHPPgAdmin\Traits;

/**
 * Common trait for triggers manipulation.
 */
trait TriggerTrait
{
    /**
     * Grabs a single trigger.
     *
     * @param string $table   The name of a table whose triggers to retrieve
     * @param string $trigger The name of the trigger to retrieve
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getTrigger($table, $trigger)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);
        $this->clean($trigger);

        $sql = "
            SELECT * FROM pg_catalog.pg_trigger t, pg_catalog.pg_class c
            WHERE t.tgrelid=c.oid AND c.relname='{$table}' AND t.tgname='{$trigger}'
                AND c.relnamespace=(
                    SELECT oid FROM pg_catalog.pg_namespace
                    WHERE nspname='{$c_schema}')";

        return $this->selectSet($sql);
    }

    /**
     * Gets the trigger constraint.
     *
     * @param array $trigger An array containing fields from the trigger table
     *
     * @return array  The trigger constraint sentence, and the $findx
     */
    private function _getTriggerConstraint($trigger)
    {
        // Constraint trigger or normal trigger
        if ($trigger['tgisconstraint']) {
            $tgdef = 'CREATE CONSTRAINT TRIGGER ';
        } else {
            $tgdef = 'CREATE TRIGGER ';
        }

        $tgdef .= "\"{$trigger['tgname']}\" ";

        // Trigger type
        $findx = 0;
        if (($trigger['tgtype'] & TRIGGER_TYPE_BEFORE) == TRIGGER_TYPE_BEFORE) {
            $tgdef .= 'BEFORE';
        } else {
            $tgdef .= 'AFTER';
        }

        if (($trigger['tgtype'] & TRIGGER_TYPE_INSERT) == TRIGGER_TYPE_INSERT) {
            $tgdef .= ' INSERT';
            ++$findx;
        }
        if (($trigger['tgtype'] & TRIGGER_TYPE_DELETE) == TRIGGER_TYPE_DELETE) {
            if ($findx > 0) {
                $tgdef .= ' OR DELETE';
            } else {
                $tgdef .= ' DELETE';
                ++$findx;
            }
        }
        if (($trigger['tgtype'] & TRIGGER_TYPE_UPDATE) == TRIGGER_TYPE_UPDATE) {
            if ($findx > 0) {
                $tgdef .= ' OR UPDATE';
            } else {
                $tgdef .= ' UPDATE';
            }
        }

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        // Table name
        $tgdef .= " ON \"{$f_schema}\".\"{$trigger['relname']}\" ";
        return [$tgdef, $findx];
    }

    /**
     * Figure out if trigger is deferrable
     *
     * @param array $trigger An array containing fields from the trigger table
     *
     * @return string  The trigger deferrability sentence part.
     */
    private function _getTriggerDeferrability($trigger)
    {
        $tgdef = '';
        if ($trigger['tgisconstraint']) {
            if ($trigger['tgconstrrelid'] != 0) {
                // Assume constrelname is not null
                $tgdef .= " FROM \"{$trigger['tgconstrrelname']}\" ";
            }
            if (!$trigger['tgdeferrable']) {
                $tgdef .= 'NOT ';
            }

            $tgdef .= 'DEFERRABLE INITIALLY ';
            if ($trigger['tginitdeferred']) {
                $tgdef .= 'DEFERRED ';
            } else {
                $tgdef .= 'IMMEDIATE ';
            }
        }

        // Row or statement
        if ($trigger['tgtype'] & TRIGGER_TYPE_ROW == TRIGGER_TYPE_ROW) {
            $tgdef .= 'FOR EACH ROW ';
        } else {
            $tgdef .= 'FOR EACH STATEMENT ';
        }
        return $tgdef;
    }

    /**
     * Defines trigger related constants
     */
    private function _defineTriggersConstants()
    {
        defined('TRIGGER_TYPE_ROW') || define('TRIGGER_TYPE_ROW', 1 << 0);

        defined('TRIGGER_TYPE_BEFORE') || define('TRIGGER_TYPE_BEFORE', 1 << 1);

        defined('TRIGGER_TYPE_INSERT') || define('TRIGGER_TYPE_INSERT', 1 << 2);

        defined('TRIGGER_TYPE_DELETE') || define('TRIGGER_TYPE_DELETE', 1 << 3);

        defined('TRIGGER_TYPE_UPDATE') || define('TRIGGER_TYPE_UPDATE', 1 << 4);
    }

    /**
     * A helper function for getTriggers that translates
     * an array of attribute numbers to an array of field names.
     * Note: Only needed for pre-7.4 servers, this function is deprecated.
     *
     * @param array $trigger An array containing fields from the trigger table
     *
     * @return string The trigger definition string
     */
    public function getTriggerDef($trigger)
    {
        $this->fieldArrayClean($trigger);
        // Constants to figure out tgtype

        $this->_defineTriggersConstants();

        $trigger['tgisconstraint'] = $this->phpBool($trigger['tgisconstraint']);
        $trigger['tgdeferrable']   = $this->phpBool($trigger['tgdeferrable']);
        $trigger['tginitdeferred'] = $this->phpBool($trigger['tginitdeferred']);

        list($tgdef, $findx) = $this->_getTriggerConstraint($trigger);
        // Deferrability

        $tgdef .= $this->_getTriggerDeferrability($trigger);

        // Execute procedure
        $tgdef .= "EXECUTE PROCEDURE \"{$trigger['tgfname']}\"(";

        // Parameters
        // Escape null characters
        $v = addcslashes($trigger['tgargs'], "\0");
        // Split on escaped null characters
        $params = explode('\\000', $v);
        for ($findx = 0; $findx < $trigger['tgnargs']; ++$findx) {
            $param = "'" . str_replace('\'', '\\\'', $params[$findx]) . "'";
            $tgdef .= $param;
            if ($findx < ($trigger['tgnargs'] - 1)) {
                $tgdef .= ', ';
            }
        }

        // Finish it off
        $tgdef .= ')';

        return $tgdef;
    }

    /**
     * Returns a list of all functions that can be used in triggers.
     *
     * @return \PHPPgAdmin\ADORecordSet all functions that can be used in triggers
     */
    public function getTriggerFunctions()
    {
        return $this->getFunctions(true, 'trigger');
    }

    abstract public function fieldClean(&$str);

    abstract public function beginTransaction();

    abstract public function rollbackTransaction();

    abstract public function endTransaction();

    abstract public function execute($sql);

    abstract public function setComment($obj_type, $obj_name, $table, $comment, $basetype = null);

    abstract public function selectSet($sql);

    abstract public function clean(&$str);
}
