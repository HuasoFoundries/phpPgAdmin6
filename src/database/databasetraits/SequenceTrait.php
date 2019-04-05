<?php

/**
 * PHPPgAdmin v6.0.0-beta.51
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for sequence manipulation.
 */
trait SequenceTrait
{
    /**
     * Returns all sequences in the current database.
     *
     * @param bool $all true to get all sequences of all schemas
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getSequences($all = false)
    {
        if ($all) {
            // Exclude pg_catalog and information_schema tables
            $sql = "SELECT n.nspname, c.relname AS seqname, u.usename AS seqowner
				FROM pg_catalog.pg_class c, pg_catalog.pg_user u, pg_catalog.pg_namespace n
				WHERE c.relowner=u.usesysid AND c.relnamespace=n.oid
				AND c.relkind = 'S'
				AND n.nspname NOT IN ('pg_catalog', 'information_schema', 'pg_toast')
				ORDER BY nspname, seqname";
        } else {
            $c_schema = $this->_schema;
            $this->clean($c_schema);
            $sql = "SELECT c.relname AS seqname, u.usename AS seqowner, pg_catalog.obj_description(c.oid, 'pg_class') AS seqcomment,
				(SELECT spcname FROM pg_catalog.pg_tablespace pt WHERE pt.oid=c.reltablespace) AS tablespace
				FROM pg_catalog.pg_class c, pg_catalog.pg_user u, pg_catalog.pg_namespace n
				WHERE c.relowner=u.usesysid AND c.relnamespace=n.oid
				AND c.relkind = 'S' AND n.nspname='{$c_schema}' ORDER BY seqname";
        }

        return $this->selectSet($sql);
    }

    /**
     * Execute nextval on a given sequence.
     *
     * @param string $sequence Sequence name
     *
     * @return int 0 if operation was successful
     */
    public function nextvalSequence($sequence)
    {
        /* This double-cleaning is deliberate */
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->clean($f_schema);
        $this->fieldClean($sequence);
        $this->clean($sequence);

        $sql = "SELECT pg_catalog.NEXTVAL('\"{$f_schema}\".\"{$sequence}\"')";

        return $this->execute($sql);
    }

    /**
     * Execute setval on a given sequence.
     *
     * @param string $sequence  Sequence name
     * @param number $nextvalue The next value
     *
     * @return int 0 if operation was successful
     */
    public function setvalSequence($sequence, $nextvalue)
    {
        /* This double-cleaning is deliberate */
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->clean($f_schema);
        $this->fieldClean($sequence);
        $this->clean($sequence);
        $this->clean($nextvalue);

        $sql = "SELECT pg_catalog.SETVAL('\"{$f_schema}\".\"{$sequence}\"', '{$nextvalue}')";

        return $this->execute($sql);
    }

    /**
     * Restart a given sequence to its start value.
     *
     * @param string $sequence Sequence name
     *
     * @return int 0 if operation was successful
     */
    public function restartSequence($sequence)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($sequence);

        $sql = "ALTER SEQUENCE \"{$f_schema}\".\"{$sequence}\" RESTART;";

        return $this->execute($sql);
    }

    /**
     * Resets a given sequence to min value of sequence.
     *
     * @param string $sequence Sequence name
     *
     * @return int 0 if operation was successful
     */
    public function resetSequence($sequence)
    {
        // Get the minimum value of the sequence
        $seq = $this->getSequence($sequence);
        if ($seq->RecordCount() != 1) {
            return -1;
        }

        $minvalue = $seq->fields['min_value'];

        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        /* This double-cleaning is deliberate */
        $this->fieldClean($sequence);
        $this->clean($sequence);

        $sql = "SELECT pg_catalog.SETVAL('\"{$f_schema}\".\"{$sequence}\"', {$minvalue})";

        return $this->execute($sql);
    }

    /**
     * Returns properties of a single sequence.
     *
     * @param string $sequence Sequence name
     *
     * @return \PHPPgAdmin\ADORecordSet A recordset
     */
    public function getSequence($sequence)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $c_sequence = $sequence;
        $this->fieldClean($sequence);
        $this->clean($c_sequence);

        $sql = "
			SELECT c.relname AS seqname, s.*,
				pg_catalog.obj_description(s.tableoid, 'pg_class') AS seqcomment,
				u.usename AS seqowner, n.nspname
			FROM \"{$sequence}\" AS s, pg_catalog.pg_class c, pg_catalog.pg_user u, pg_catalog.pg_namespace n
			WHERE c.relowner=u.usesysid AND c.relnamespace=n.oid
				AND c.relname = '{$c_sequence}' AND c.relkind = 'S' AND n.nspname='{$c_schema}'
				AND n.oid = c.relnamespace";

        return $this->selectSet($sql);
    }

    /**
     * Creates a new sequence.
     *
     * @param string $sequence    Sequence name
     * @param number $increment   The increment
     * @param number $minvalue    The min value
     * @param number $maxvalue    The max value
     * @param number $startvalue  The starting value
     * @param number $cachevalue  The cache value
     * @param bool   $cycledvalue True if cycled, false otherwise
     *
     * @return int 0 if operation was successful
     */
    public function createSequence(
        $sequence,
        $increment,
        $minvalue,
        $maxvalue,
        $startvalue,
        $cachevalue,
        $cycledvalue
    ) {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($sequence);
        $this->clean($increment);
        $this->clean($minvalue);
        $this->clean($maxvalue);
        $this->clean($startvalue);
        $this->clean($cachevalue);

        $sql = "CREATE SEQUENCE \"{$f_schema}\".\"{$sequence}\"";
        if ($increment != '') {
            $sql .= " INCREMENT {$increment}";
        }

        if ($minvalue != '') {
            $sql .= " MINVALUE {$minvalue}";
        }

        if ($maxvalue != '') {
            $sql .= " MAXVALUE {$maxvalue}";
        }

        if ($startvalue != '') {
            $sql .= " START {$startvalue}";
        }

        if ($cachevalue != '') {
            $sql .= " CACHE {$cachevalue}";
        }

        if ($cycledvalue) {
            $sql .= ' CYCLE';
        }

        return $this->execute($sql);
    }

    /**
     * Alters a sequence.
     *
     * @param string    $sequence     The name of the sequence
     * @param string    $name         The new name for the sequence
     * @param string    $comment      The comment on the sequence
     * @param string    $owner        The new owner for the sequence
     * @param string    $schema       The new schema for the sequence
     * @param string    $increment    The increment
     * @param number    $minvalue     The min value
     * @param number    $maxvalue     The max value
     * @param number    $restartvalue The starting value
     * @param number    $cachevalue   The cache value
     * @param null|bool $cycledvalue  True if cycled, false otherwise
     * @param number    $startvalue   The sequence start value when issueing a restart
     *
     * @return bool|int 0 success
     */
    public function alterSequence(
        $sequence,
        $name,
        $comment,
        $owner = null,
        $schema = null,
        $increment = null,
        $minvalue = null,
        $maxvalue = null,
        $restartvalue = null,
        $cachevalue = null,
        $cycledvalue = null,
        $startvalue = null
    ) {
        $this->fieldClean($sequence);

        $data = $this->getSequence($sequence);

        if ($data->RecordCount() != 1) {
            return -2;
        }

        $status = $this->beginTransaction();
        if ($status != 0) {
            $this->rollbackTransaction();

            return -1;
        }

        $status = $this->_alterSequence(
            $data,
            $name,
            $comment,
            $owner,
            $schema,
            $increment,
            $minvalue,
            $maxvalue,
            $restartvalue,
            $cachevalue,
            $cycledvalue,
            $startvalue
        );

        if ($status != 0) {
            $this->rollbackTransaction();

            return $status;
        }

        return $this->endTransaction();
    }

    /**
     * Protected method which alter a sequence
     * SHOULDN'T BE CALLED OUTSIDE OF A TRANSACTION.
     *
     * @param \PHPPgAdmin\ADORecordSet $seqrs        The sequence recordSet returned by getSequence()
     * @param string                   $name         The new name for the sequence
     * @param string                   $comment      The comment on the sequence
     * @param string                   $owner        The new owner for the sequence
     * @param string                   $schema       The new schema for the sequence
     * @param string                   $increment    The increment
     * @param string                   $minvalue     The min value
     * @param string                   $maxvalue     The max value
     * @param string                   $restartvalue The starting value
     * @param string                   $cachevalue   The cache value
     * @param null|bool                $cycledvalue  True if cycled, false otherwise
     * @param string                   $startvalue   The sequence start value when issueing a restart
     *
     * @return int 0 success
     */
    protected function _alterSequence(
        $seqrs,
        $name,
        $comment,
        $owner,
        $schema,
        $increment,
        $minvalue,
        $maxvalue,
        $restartvalue,
        $cachevalue,
        $cycledvalue,
        $startvalue
    ) {
        $this->fieldArrayClean($seqrs->fields);

        // Comment
        $status = $this->setComment('SEQUENCE', $seqrs->fields['seqname'], '', $comment);
        if ($status != 0) {
            return -4;
        }

        // Owner
        $this->fieldClean($owner);
        $status = $this->alterSequenceOwner($seqrs, $owner);
        if ($status != 0) {
            return -5;
        }

        // Props
        $this->clean($increment);
        $this->clean($minvalue);
        $this->clean($maxvalue);
        $this->clean($restartvalue);
        $this->clean($cachevalue);
        $this->clean($cycledvalue);
        $this->clean($startvalue);
        $status = $this->alterSequenceProps(
            $seqrs,
            $increment,
            $minvalue,
            $maxvalue,
            $restartvalue,
            $cachevalue,
            $cycledvalue,
            $startvalue
        );
        if ($status != 0) {
            return -6;
        }

        // Rename
        $this->fieldClean($name);
        $status = $this->alterSequenceName($seqrs, $name);
        if ($status != 0) {
            return -3;
        }

        // Schema
        $this->clean($schema);
        $status = $this->alterSequenceSchema($seqrs, $schema);
        if ($status != 0) {
            return -7;
        }

        return 0;
    }

    // Index functions

    /**
     * Alter a sequence's owner.
     *
     * @param \PHPPgAdmin\ADORecordSet $seqrs The sequence RecordSet returned by getSequence()
     * @param string                   $owner the new owner of the sequence
     *
     * @return int 0 if operation was successful
     *
     * @internal string $name new owner for the sequence
     */
    public function alterSequenceOwner($seqrs, $owner)
    {
        // If owner has been changed, then do the alteration.  We are
        // careful to avoid this generally as changing owner is a
        // superuser only function.
        /* vars are cleaned in _alterSequence */
        if (!empty($owner) && ($seqrs->fields['seqowner'] != $owner)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $sql = "ALTER SEQUENCE \"{$f_schema}\".\"{$seqrs->fields['seqname']}\" OWNER TO \"{$owner}\"";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Alter a sequence's properties.
     *
     * @param \PHPPgAdmin\ADORecordSet $seqrs        The sequence RecordSet returned by getSequence()
     * @param number                   $increment    The sequence incremental value
     * @param number                   $minvalue     The sequence minimum value
     * @param number                   $maxvalue     The sequence maximum value
     * @param number                   $restartvalue The sequence current value
     * @param number                   $cachevalue   The sequence cache value
     * @param null|bool                $cycledvalue  Sequence can cycle ?
     * @param number                   $startvalue   The sequence start value when issueing a restart
     *
     * @return int 0 if operation was successful
     */
    public function alterSequenceProps(
        $seqrs,
        $increment,
        $minvalue,
        $maxvalue,
        $restartvalue,
        $cachevalue,
        $cycledvalue,
        $startvalue
    ) {
        $sql = '';
        /* vars are cleaned in _alterSequence */
        if (!empty($increment) && ($increment != $seqrs->fields['increment_by'])) {
            $sql .= " INCREMENT {$increment}";
        }

        if (!empty($minvalue) && ($minvalue != $seqrs->fields['min_value'])) {
            $sql .= " MINVALUE {$minvalue}";
        }

        if (!empty($maxvalue) && ($maxvalue != $seqrs->fields['max_value'])) {
            $sql .= " MAXVALUE {$maxvalue}";
        }

        if (!empty($restartvalue) && ($restartvalue != $seqrs->fields['last_value'])) {
            $sql .= " RESTART {$restartvalue}";
        }

        if (!empty($cachevalue) && ($cachevalue != $seqrs->fields['cache_value'])) {
            $sql .= " CACHE {$cachevalue}";
        }

        if (!empty($startvalue) && ($startvalue != $seqrs->fields['start_value'])) {
            $sql .= " START {$startvalue}";
        }

        // toggle cycle yes/no
        if (!is_null($cycledvalue)) {
            $sql .= (!$cycledvalue ? ' NO ' : '').' CYCLE';
        }

        if ($sql != '') {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $sql = "ALTER SEQUENCE \"{$f_schema}\".\"{$seqrs->fields['seqname']}\" {$sql}";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Rename a sequence.
     *
     * @param \PHPPgAdmin\ADORecordSet $seqrs The sequence RecordSet returned by getSequence()
     * @param string                   $name  The new name for the sequence
     *
     * @return int 0 if operation was successful
     */
    public function alterSequenceName($seqrs, $name)
    {
        /* vars are cleaned in _alterSequence */
        if (!empty($name) && ($seqrs->fields['seqname'] != $name)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $sql    = "ALTER SEQUENCE \"{$f_schema}\".\"{$seqrs->fields['seqname']}\" RENAME TO \"{$name}\"";
            $status = $this->execute($sql);
            if ($status == 0) {
                $seqrs->fields['seqname'] = $name;
            } else {
                return $status;
            }
        }

        return 0;
    }

    /**
     * Alter a sequence's schema.
     *
     * @param \PHPPgAdmin\ADORecordSet $seqrs  The sequence RecordSet returned by getSequence()
     * @param string                   $schema
     *
     * @return int 0 if operation was successful
     *
     * @internal param The $name new schema for the sequence
     */
    public function alterSequenceSchema($seqrs, $schema)
    {
        /* vars are cleaned in _alterSequence */
        if (!empty($schema) && ($seqrs->fields['nspname'] != $schema)) {
            $f_schema = $this->_schema;
            $this->fieldClean($f_schema);
            $sql = "ALTER SEQUENCE \"{$f_schema}\".\"{$seqrs->fields['seqname']}\" SET SCHEMA {$schema}";

            return $this->execute($sql);
        }

        return 0;
    }

    /**
     * Drops a given sequence.
     *
     * @param $sequence Sequence name
     * @param $cascade  True to cascade drop, false to restrict
     *
     * @return int 0 if operation was successful
     */
    public function dropSequence($sequence, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($sequence);

        $sql = "DROP SEQUENCE \"{$f_schema}\".\"{$sequence}\"";
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

    abstract public function fieldArrayClean(&$arr);
}
