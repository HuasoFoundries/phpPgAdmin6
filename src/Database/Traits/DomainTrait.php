<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for domains manipulation.
 */
trait DomainTrait
{
    /**
     * Gets all information for a single domain.
     *
     * @param string $domain The name of the domain to fetch
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getDomain($domain)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($domain);

        $sql = \sprintf(
            '
            SELECT
                t.typname AS domname,
                pg_catalog.format_type(t.typbasetype, t.typtypmod) AS domtype,
                t.typnotnull AS domnotnull,
                t.typdefault AS domdef,
                pg_catalog.pg_get_userbyid(t.typowner) AS domowner,
                pg_catalog.obj_description(t.oid, \'pg_type\') AS domcomment
            FROM
                pg_catalog.pg_type t
            WHERE
                t.typtype = \'d\'
                AND t.typname = \'%s\'
                AND t.typnamespace = (SELECT oid FROM pg_catalog.pg_namespace
                    WHERE nspname = \'%s\')',
            $domain,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Return all domains in current schema.  Excludes domain constraints.
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getDomains()
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);

        $sql = \sprintf(
            '
            SELECT
                t.typname AS domname,
                pg_catalog.format_type(t.typbasetype, t.typtypmod) AS domtype,
                t.typnotnull AS domnotnull,
                t.typdefault AS domdef,
                pg_catalog.pg_get_userbyid(t.typowner) AS domowner,
                pg_catalog.obj_description(t.oid, \'pg_type\') AS domcomment
            FROM
                pg_catalog.pg_type t
            WHERE
                t.typtype = \'d\'
                AND t.typnamespace = (SELECT oid FROM pg_catalog.pg_namespace
                    WHERE nspname=\'%s\')
            ORDER BY t.typname',
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Get domain constraints.
     *
     * @param string $domain The name of the domain whose constraints to fetch
     *
     * @return \ADORecordSet|bool|int|string
     */
    public function getDomainConstraints($domain)
    {
        $c_schema = $this->_schema;
        $this->clean($c_schema);
        $this->clean($domain);

        $sql = \sprintf(
            '
            SELECT
                conname,
                contype,
                pg_catalog.pg_get_constraintdef(oid, true) AS consrc
            FROM
                pg_catalog.pg_constraint
            WHERE
                contypid = (
                    SELECT oid FROM pg_catalog.pg_type
                    WHERE typname=\'%s\'
                        AND typnamespace = (
                            SELECT oid FROM pg_catalog.pg_namespace
                            WHERE nspname = \'%s\')
                )
            ORDER BY conname',
            $domain,
            $c_schema
        );

        return $this->selectSet($sql);
    }

    /**
     * Creates a domain.
     *
     * @param string $domain  The name of the domain to create
     * @param string $type    The base type for the domain
     * @param string $length  Optional type length
     * @param bool   $array   True for array type, false otherwise
     * @param bool   $notnull True for NOT NULL, false otherwise
     * @param string $default Default value for domain
     * @param string $check   A CHECK constraint if there is one
     *
     * @return int|string
     */
    public function createDomain($domain, $type, $length, $array, $notnull, $default, $check)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($domain);

        $sql = \sprintf(
            'CREATE DOMAIN "%s"."%s" AS ',
            $f_schema,
            $domain
        );

        if ('' === $length) {
            $sql .= $type;
        } else {
            switch ($type) {
                // Have to account for weird placing of length for with/without
                // time zone types
                case 'timestamp with time zone':
                case 'timestamp without time zone':
                    $qual = \mb_substr($type, 9);
                    $sql .= \sprintf(
                        'timestamp(%s)%s',
                        $length,
                        $qual
                    );

                    break;
                case 'time with time zone':
                case 'time without time zone':
                    $qual = \mb_substr($type, 4);
                    $sql .= \sprintf(
                        'time(%s)%s',
                        $length,
                        $qual
                    );

                    break;

                default:
                    $sql .= \sprintf(
                        '%s(%s)',
                        $type,
                        $length
                    );
            }
        }

        // Add array qualifier, if requested
        if ($array) {
            $sql .= '[]';
        }

        if ($notnull) {
            $sql .= ' NOT NULL';
        }

        if ('' !== $default) {
            $sql .= \sprintf(
                ' DEFAULT %s',
                $default
            );
        }

        if ($this->hasDomainConstraints() && '' !== $check) {
            $sql .= \sprintf(
                ' CHECK (%s)',
                $check
            );
        }

        return $this->execute($sql);
    }

    /**
     * Alters a domain.
     *
     * @param string $domain     The domain to alter
     * @param string $domdefault The domain default
     * @param bool   $domnotnull True for NOT NULL, false otherwise
     * @param string $domowner   The domain owner
     *
     * @return int
     *
     * @psalm-return -4|-3|-2|-1|0|1
     */
    public function alterDomain($domain, $domdefault, $domnotnull, $domowner)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($domain);
        $this->fieldClean($domowner);

        $status = $this->beginTransaction();

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -1;
        }

        // Default
        if ('' === $domdefault) {
            $sql = \sprintf(
                'ALTER DOMAIN "%s"."%s" DROP DEFAULT',
                $f_schema,
                $domain
            );
        } else {
            $sql = \sprintf(
                'ALTER DOMAIN "%s"."%s" SET DEFAULT %s',
                $f_schema,
                $domain,
                $domdefault
            );
        }

        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -2;
        }

        // NOT NULL
        if ($domnotnull) {
            $sql = \sprintf(
                'ALTER DOMAIN "%s"."%s" SET NOT NULL',
                $f_schema,
                $domain
            );
        } else {
            $sql = \sprintf(
                'ALTER DOMAIN "%s"."%s" DROP NOT NULL',
                $f_schema,
                $domain
            );
        }

        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -3;
        }

        // Owner
        $sql = \sprintf(
            'ALTER DOMAIN "%s"."%s" OWNER TO "%s"',
            $f_schema,
            $domain,
            $domowner
        );

        $status = $this->execute($sql);

        if (0 !== $status) {
            $this->rollbackTransaction();

            return -4;
        }

        return $this->endTransaction();
    }

    /**
     * Drops a domain.
     *
     * @param string $domain  The name of the domain to drop
     * @param string $cascade True to cascade drop, false to restrict
     *
     * @return int|string
     */
    public function dropDomain($domain, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($domain);

        $sql = \sprintf(
            'DROP DOMAIN "%s"."%s"',
            $f_schema,
            $domain
        );

        if ($cascade) {
            $sql .= ' CASCADE';
        }

        return $this->execute($sql);
    }

    /**
     * Adds a check constraint to a domain.
     *
     * @param string $domain     The domain to which to add the check
     * @param string $definition The definition of the check
     * @param string $name       (optional) The name to give the check, otherwise default name is assigned
     *
     * @return int|string
     */
    public function addDomainCheckConstraint($domain, $definition, $name = '')
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($domain);
        $this->fieldClean($name);

        $sql = \sprintf(
            'ALTER DOMAIN "%s"."%s" ADD ',
            $f_schema,
            $domain
        );

        if ('' !== $name) {
            $sql .= \sprintf(
                'CONSTRAINT "%s" ',
                $name
            );
        }

        $sql .= \sprintf(
            'CHECK (%s)',
            $definition
        );

        return $this->execute($sql);
    }

    /**
     * Drops a domain constraint.
     *
     * @param string $domain     The domain from which to remove the constraint
     * @param string $constraint The constraint to remove
     * @param bool   $cascade    True to cascade, false otherwise
     *
     * @return int|string
     */
    public function dropDomainConstraint($domain, $constraint, $cascade)
    {
        $f_schema = $this->_schema;
        $this->fieldClean($f_schema);
        $this->fieldClean($domain);
        $this->fieldClean($constraint);

        $sql = \sprintf(
            'ALTER DOMAIN "%s"."%s" DROP CONSTRAINT "%s"',
            $f_schema,
            $domain,
            $constraint
        );

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

    abstract public function hasDomainConstraints();
}
