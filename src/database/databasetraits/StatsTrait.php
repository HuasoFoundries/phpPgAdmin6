<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Database\Traits;

use PHPPgAdmin\ADORecordSet;

/**
 * Common trait to retrieve stats on database objects.
 */
trait StatsTrait
{
    /**
     * Fetches statistics for a database.
     *
     * @param string $database The database to fetch stats for
     *
     * @return \RecordSet|int|string
     */
    public function getStatsDatabase($database)
    {
        $this->clean($database);

        $sql = \sprintf(
            'SELECT * FROM pg_stat_database WHERE datname=\'%s\'',
            $database
        );

        return $this->selectSet($sql);
    }

    /**
     * Fetches tuple statistics for a table.
     *
     * @param string $table The table to fetch stats for
     *
     * @return \RecordSet|int|string
     */
    public function getStatsTableTuples($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT * FROM pg_stat_all_tables
            WHERE schemaname=\'%s\' AND relname=\'%s\'',
            $c_schema,
            $table
        );

        return $this->selectSet($sql);
    }

    /**
     * Fetches I/0 statistics for a table.
     *
     * @param string $table The table to fetch stats for
     *
     * @return \RecordSet|int|string
     */
    public function getStatsTableIO($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT * FROM pg_statio_all_tables
            WHERE schemaname=\'%s\' AND relname=\'%s\'',
            $c_schema,
            $table
        );

        return $this->selectSet($sql);
    }

    /**
     * Fetches tuple statistics for all indexes on a table.
     *
     * @param string $table The table to fetch index stats for
     *
     * @return \RecordSet|int|string
     */
    public function getStatsIndexTuples($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT * FROM pg_stat_all_indexes
            WHERE schemaname=\'%s\' AND relname=\'%s\' ORDER BY indexrelname',
            $c_schema,
            $table
        );

        return $this->selectSet($sql);
    }

    /**
     * Fetches I/0 statistics for all indexes on a table.
     *
     * @param string $table The table to fetch index stats for
     *
     * @return \RecordSet|int|string
     */
    public function getStatsIndexIO($table)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($table);

        $sql = \sprintf(
            'SELECT * FROM pg_statio_all_indexes
            WHERE schemaname=\'%s\' AND relname=\'%s\'
            ORDER BY indexrelname',
            $c_schema,
            $table
        );

        return $this->selectSet($sql);
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
