<?php

/**
 * PHPPgAdmin v6.0.0-beta.47
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for trigger and rules manipulation.
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
     * Creates a trigger.
     *
     * @param string $tgname      The name of the trigger to create
     * @param string $table       The name of the table
     * @param string $tgproc      The function to execute
     * @param string $tgtime      BEFORE or AFTER
     * @param string $tgevent     Event
     * @param string $tgfrequency
     * @param string $tgargs      The function arguments
     *
     * @return int 0 if operation was successful
     */
    public function createTrigger($tgname, $table, $tgproc, $tgtime, $tgevent, $tgfrequency, $tgargs)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);
        $this->fieldClean($tgproc);

        /* No Statement Level Triggers in PostgreSQL (by now) */
        $sql = "CREATE TRIGGER \"{$tgname}\" {$tgtime}
                {$tgevent} ON \"{$f_schema}\".\"{$table}\"
                FOR EACH {$tgfrequency} EXECUTE PROCEDURE \"{$tgproc}\"({$tgargs})";

        return $this->execute($sql);
    }

    /**
     * Alters a trigger.
     *
     * @param string $table   The name of the table containing the trigger
     * @param string $trigger The name of the trigger to alter
     * @param string $name    The new name for the trigger
     *
     * @return int 0 if operation was successful
     */
    public function alterTrigger($table, $trigger, $name)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($table);
        $this->fieldClean($trigger);
        $this->fieldClean($name);

        $sql = "ALTER TRIGGER \"{$trigger}\" ON \"{$f_schema}\".\"{$table}\" RENAME TO \"{$name}\"";

        return $this->execute($sql);
    }

    /**
     * Drops a trigger.
     *
     * @param string $tgname  The name of the trigger to drop
     * @param string $table   The table from which to drop the trigger
     * @param bool   $cascade True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropTrigger($tgname, $table, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);

        $sql = "DROP TRIGGER \"{$tgname}\" ON \"{$f_schema}\".\"{$table}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Enables a trigger.
     *
     * @param string $tgname The name of the trigger to enable
     * @param string $table  The table in which to enable the trigger
     *
     * @return int 0 if operation was successful
     */
    public function enableTrigger($tgname, $table)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" ENABLE TRIGGER \"{$tgname}\"";

        return $this->execute($sql);
    }

    /**
     * Disables a trigger.
     *
     * @param string $tgname The name of the trigger to disable
     * @param string $table  The table in which to disable the trigger
     *
     * @return int 0 if operation was successful
     */
    public function disableTrigger($tgname, $table)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($tgname);
        $this->fieldClean($table);

        $sql = "ALTER TABLE \"{$f_schema}\".\"{$table}\" DISABLE TRIGGER \"{$tgname}\"";

        return $this->execute($sql);
    }

    // Rule functions

    // Operator Class functions

    /**
     * Edits a rule on a table OR view.
     *
     * @param string $name    The name of the new rule
     * @param string $event   SELECT, INSERT, UPDATE or DELETE
     * @param string $table   Table on which to create the rule
     * @param string $where   Where to execute the rule, '' indicates always
     * @param bool   $instead True if an INSTEAD rule, false otherwise
     * @param string $type    NOTHING for a do nothing rule, SOMETHING to use given action
     * @param string $action  The action to take
     *
     * @return int 0 if operation was successful
     */
    public function setRule($name, $event, $table, $where, $instead, $type, $action)
    {
        return $this->createRule($name, $event, $table, $where, $instead, $type, $action, true);
    }

    // FTS functions

    /**
     * Creates a rule.
     *
     * @param string $name    The name of the new rule
     * @param string $event   SELECT, INSERT, UPDATE or DELETE
     * @param string $table   Table on which to create the rule
     * @param string $where   When to execute the rule, '' indicates always
     * @param bool   $instead True if an INSTEAD rule, false otherwise
     * @param string $type    NOTHING for a do nothing rule, SOMETHING to use given action
     * @param string $action  The action to take
     * @param bool   $replace (optional) True to replace existing rule, false
     *                        otherwise
     *
     * @return int 0 if operation was successful
     */
    public function createRule($name, $event, $table, $where, $instead, $type, $action, $replace = false)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($name);
        $this->fieldClean($table);
        if (!in_array($event, $this->rule_events, true)) {
            return -1;
        }

        $sql = 'CREATE';
        if ($replace) {
            $sql .= ' OR REPLACE';
        }

        $sql .= " RULE \"{$name}\" AS ON {$event} TO \"{$f_schema}\".\"{$table}\"";
        // Can't escape WHERE clause
        if ($where != '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= ' DO';
        if ($instead) {
            $sql .= ' INSTEAD';
        }

        if ($type == 'NOTHING') {
            $sql .= ' NOTHING';
        } else {
            $sql .= " ({$action})";
        }

        return $this->execute($sql);
    }

    /**
     * Removes a rule from a table OR view.
     *
     * @param string $rule     The rule to drop
     * @param string $relation The relation from which to drop
     * @param string $cascade  True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropRule($rule, $relation, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($rule);
        $this->fieldClean($relation);

        $sql = "DROP RULE \"{$rule}\" ON \"{$f_schema}\".\"{$relation}\"";
        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    abstract public function fieldClean(&$str);

    abstract public function beginTransaction();

    abstract public function rollbackTransaction();

    abstract public function endTransaction();

    abstract public function execute($sql);

    abstract public function setComment($obj_type, $obj_name, $table, $comment, $basetype = null);

    abstract public function selectSet($sql);

    abstract public function clean(&$str);

    abstract public function phpBool($parameter);

    abstract public function hasCreateTableLikeWithConstraints();

    abstract public function hasCreateTableLikeWithIndexes();

    abstract public function hasTablespaces();

    abstract public function delete($table, $conditions, $schema = '');

    abstract public function fieldArrayClean(&$arr);

    abstract public function hasCreateFieldWithConstraints();

    abstract public function getAttributeNames($table, $atts);
}
