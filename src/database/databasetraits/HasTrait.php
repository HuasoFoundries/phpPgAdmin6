<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
 */

namespace PHPPgAdmin\Database\Traits;

/**
 * Common trait for flags telling if the driver supports certain attributes/operations.
 */
trait HasTrait
{
    public $conn;

    // The backend platform.  Set to UNKNOWN by default.
    public $platform = 'UNKNOWN';

    public $major_version = 9.6;
    // Max object name length
    public $_maxNameLen = 63;
    // Store the current schema
    public $_schema;
    // Map of database encoding names to HTTP encoding names.  If a
    // database encoding does not appear in this list, then its HTTP
    // encoding name is the same as its database encoding name.
    public $codemap = [
        'BIG5'       => 'BIG5',
        'EUC_CN'     => 'GB2312',
        'EUC_JP'     => 'EUC-JP',
        'EUC_KR'     => 'EUC-KR',
        'EUC_TW'     => 'EUC-TW',
        'GB18030'    => 'GB18030',
        'GBK'        => 'GB2312',
        'ISO_8859_5' => 'ISO-8859-5',
        'ISO_8859_6' => 'ISO-8859-6',
        'ISO_8859_7' => 'ISO-8859-7',
        'ISO_8859_8' => 'ISO-8859-8',
        'JOHAB'      => 'CP1361',
        'KOI8'       => 'KOI8-R',
        'LATIN1'     => 'ISO-8859-1',
        'LATIN2'     => 'ISO-8859-2',
        'LATIN3'     => 'ISO-8859-3',
        'LATIN4'     => 'ISO-8859-4',
        'LATIN5'     => 'ISO-8859-9',
        'LATIN6'     => 'ISO-8859-10',
        'LATIN7'     => 'ISO-8859-13',
        'LATIN8'     => 'ISO-8859-14',
        'LATIN9'     => 'ISO-8859-15',
        'LATIN10'    => 'ISO-8859-16',
        'SJIS'       => 'SHIFT_JIS',
        'SQL_ASCII'  => 'US-ASCII',
        'UHC'        => 'WIN949',
        'UTF8'       => 'UTF-8',
        'WIN866'     => 'CP866',
        'WIN874'     => 'CP874',
        'WIN1250'    => 'CP1250',
        'WIN1251'    => 'CP1251',
        'WIN1252'    => 'CP1252',
        'WIN1256'    => 'CP1256',
        'WIN1258'    => 'CP1258',
    ];
    public $defaultprops = ['', '', ''];
    // Extra "magic" types.  BIGSERIAL was added in PostgreSQL 7.2.
    public $extraTypes = ['SERIAL', 'BIGSERIAL'];
    // Foreign key stuff.  First element MUST be the default.
    public $fkactions    = ['NO ACTION', 'RESTRICT', 'CASCADE', 'SET NULL', 'SET DEFAULT'];
    public $fkdeferrable = ['NOT DEFERRABLE', 'DEFERRABLE'];
    public $fkinitial    = ['INITIALLY IMMEDIATE', 'INITIALLY DEFERRED'];
    public $fkmatches    = ['MATCH SIMPLE', 'MATCH FULL'];
    // Function properties
    public $funcprops = [
        ['', 'VOLATILE', 'IMMUTABLE', 'STABLE'],
        ['', 'CALLED ON NULL INPUT', 'RETURNS NULL ON NULL INPUT'],
        ['', 'SECURITY INVOKER', 'SECURITY DEFINER'],
    ];

    // Default help URL
    public $help_base;
    // Help sub pages
    public $help_page;
    // Name of id column
    public $id = 'oid';

    // Supported join operations for use with view wizard
    public $joinOps = ['INNER JOIN' => 'INNER JOIN', 'LEFT JOIN' => 'LEFT JOIN', 'RIGHT JOIN' => 'RIGHT JOIN', 'FULL JOIN' => 'FULL JOIN'];
    // Map of internal language name to syntax highlighting name
    public $langmap = [
        'sql'       => 'SQL',
        'plpgsql'   => 'SQL',
        'php'       => 'PHP',
        'phpu'      => 'PHP',
        'plphp'     => 'PHP',
        'plphpu'    => 'PHP',
        'perl'      => 'Perl',
        'perlu'     => 'Perl',
        'plperl'    => 'Perl',
        'plperlu'   => 'Perl',
        'java'      => 'Java',
        'javau'     => 'Java',
        'pljava'    => 'Java',
        'pljavau'   => 'Java',
        'plj'       => 'Java',
        'plju'      => 'Java',
        'python'    => 'Python',
        'pythonu'   => 'Python',
        'plpython'  => 'Python',
        'plpythonu' => 'Python',
        'ruby'      => 'Ruby',
        'rubyu'     => 'Ruby',
        'plruby'    => 'Ruby',
        'plrubyu'   => 'Ruby',
    ];
    // Predefined size types
    public $predefined_size_types = [
        'abstime',
        'aclitem',
        'bigserial',
        'boolean',
        'bytea',
        'cid',
        'cidr',
        'circle',
        'date',
        'float4',
        'float8',
        'gtsvector',
        'inet',
        'int2',
        'int4',
        'int8',
        'macaddr',
        'money',
        'oid',
        'path',
        'polygon',
        'refcursor',
        'regclass',
        'regoper',
        'regoperator',
        'regproc',
        'regprocedure',
        'regtype',
        'reltime',
        'serial',
        'smgr',
        'text',
        'tid',
        'tinterval',
        'tsquery',
        'tsvector',
        'varbit',
        'void',
        'xid',
    ];
    // List of all legal privileges that can be applied to different types
    // of objects.
    public $privlist = [
        'table'      => ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'ALL PRIVILEGES'],
        'view'       => ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'REFERENCES', 'TRIGGER', 'ALL PRIVILEGES'],
        'sequence'   => ['SELECT', 'UPDATE', 'ALL PRIVILEGES'],
        'database'   => ['CREATE', 'TEMPORARY', 'CONNECT', 'ALL PRIVILEGES'],
        'function'   => ['EXECUTE', 'ALL PRIVILEGES'],
        'language'   => ['USAGE', 'ALL PRIVILEGES'],
        'schema'     => ['CREATE', 'USAGE', 'ALL PRIVILEGES'],
        'tablespace' => ['CREATE', 'ALL PRIVILEGES'],
        'column'     => ['SELECT', 'INSERT', 'UPDATE', 'REFERENCES', 'ALL PRIVILEGES'],
    ];
    // List of characters in acl lists and the privileges they
    // refer to.
    public $privmap = [
        'r' => 'SELECT',
        'w' => 'UPDATE',
        'a' => 'INSERT',
        'd' => 'DELETE',
        'D' => 'TRUNCATE',
        'R' => 'RULE',
        'x' => 'REFERENCES',
        't' => 'TRIGGER',
        'X' => 'EXECUTE',
        'U' => 'USAGE',
        'C' => 'CREATE',
        'T' => 'TEMPORARY',
        'c' => 'CONNECT',
    ];
    // Rule action types
    public $rule_events = ['SELECT', 'INSERT', 'UPDATE', 'DELETE'];
    // Select operators
    public $selectOps = [
        '='                   => 'i',
        '!='                  => 'i',
        '<'                   => 'i',
        '>'                   => 'i',
        '<='                  => 'i',
        '>='                  => 'i',
        '<<'                  => 'i',
        '>>'                  => 'i',
        '<<='                 => 'i',
        '>>='                 => 'i',
        'LIKE'                => 'i',
        'NOT LIKE'            => 'i',
        'ILIKE'               => 'i',
        'NOT ILIKE'           => 'i',
        'SIMILAR TO'          => 'i',
        'NOT SIMILAR TO'      => 'i',
        '~'                   => 'i',
        '!~'                  => 'i',
        '~*'                  => 'i',
        '!~*'                 => 'i',
        'IS NULL'             => 'p',
        'IS NOT NULL'         => 'p',
        'IN'                  => 'x',
        'NOT IN'              => 'x',
        '@@'                  => 'i',
        '@@@'                 => 'i',
        '@>'                  => 'i',
        '<@'                  => 'i',
        '@@ to_tsquery'       => 't',
        '@@@ to_tsquery'      => 't',
        '@> to_tsquery'       => 't',
        '<@ to_tsquery'       => 't',
        '@@ plainto_tsquery'  => 't',
        '@@@ plainto_tsquery' => 't',
        '@> plainto_tsquery'  => 't',
        '<@ plainto_tsquery'  => 't',
    ];
    // Array of allowed trigger events
    public $triggerEvents = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'INSERT OR UPDATE',
        'INSERT OR DELETE',
        'DELETE OR UPDATE',
        'INSERT OR DELETE OR UPDATE',
    ];
    // When to execute the trigger
    public $triggerExecTimes = ['BEFORE', 'AFTER'];
    // How often to execute the trigger
    public $triggerFrequency = ['ROW', 'STATEMENT'];
    // Array of allowed type alignments
    public $typAligns = ['char', 'int2', 'int4', 'double'];
    // The default type alignment
    public $typAlignDef = 'int4';
    // Default index type
    public $typIndexDef = 'BTREE';
    // Array of allowed index types
    public $typIndexes = ['BTREE', 'RTREE', 'GIST', 'GIN', 'HASH'];
    // Array of allowed type storage attributes
    public $typStorages = ['plain', 'external', 'extended', 'main'];
    // The default type storage
    public $typStorageDef = 'plain';

    /**
     * Determines if it has tablespaces.
     *
     * @return bool true if has tablespaces, False otherwise
     */
    public function hasTablespaces()
    {
        return true;
    }

    /**
     * Determines if it has shared comments.
     *
     * @return bool true if has shared comments, False otherwise
     */
    public function hasSharedComments()
    {
        return true;
    }

    /**
     * Determines if it has roles.
     *
     * @return bool true if has roles, False otherwise
     */
    public function hasRoles()
    {
        return true;
    }

    /**
     * Determines if it has grant option.
     *
     * @return bool true if has grant option, False otherwise
     */
    public function hasGrantOption()
    {
        return true;
    }

    /**
     * Determines if it has create table like with constraints.
     *
     * @return bool true if has create table like with constraints, False otherwise
     */
    public function hasCreateTableLikeWithConstraints()
    {
        return true;
    }

    /**
     * Determines if it has create table like with indexes.
     *
     * @return bool true if has create table like with indexes, False otherwise
     */
    public function hasCreateTableLikeWithIndexes()
    {
        return true;
    }

    /**
     * Determines if it has create field with constraints.
     *
     * @return bool true if has create field with constraints, False otherwise
     */
    public function hasCreateFieldWithConstraints()
    {
        return true;
    }

    /**
     * Determines if it has domain constraints.
     *
     * @return bool true if has domain constraints, False otherwise
     */
    public function hasDomainConstraints()
    {
        return true;
    }

    /**
     * Determines if it has function alter owner.
     *
     * @return bool true if has function alter owner, False otherwise
     */
    public function hasFunctionAlterOwner()
    {
        return true;
    }

    /**
     * Determines if it has function alter schema.
     *
     * @return bool true if has function alter schema, False otherwise
     */
    public function hasFunctionAlterSchema()
    {
        return true;
    }

    /**
     * Determines if it has read only queries.
     *
     * @return bool true if has read only queries, False otherwise
     */
    public function hasReadOnlyQueries()
    {
        return true;
    }

    /**
     * Determines if it has aggregate sort operation.
     *
     * @return bool true if has aggregate sort operation, False otherwise
     */
    public function hasAggregateSortOp()
    {
        return true;
    }

    /**
     * Determines if it has alter aggregate.
     *
     * @return bool true if has alter aggregate, False otherwise
     */
    public function hasAlterAggregate()
    {
        return true;
    }

    /**
     * Determines if it has alter column type.
     *
     * @return bool true if has alter column type, False otherwise
     */
    public function hasAlterColumnType()
    {
        return true;
    }

    /**
     * Determines if it has alter database owner.
     *
     * @return bool true if has alter database owner, False otherwise
     */
    public function hasAlterDatabaseOwner()
    {
        return true;
    }

    /**
     * Determines if it has alter schema.
     *
     * @return bool true if has alter schema, False otherwise
     */
    public function hasAlterSchema()
    {
        return true;
    }

    /**
     * Determines if it has alter schema owner.
     *
     * @return bool true if has alter schema owner, False otherwise
     */
    public function hasAlterSchemaOwner()
    {
        return true;
    }

    /**
     * Determines if it has alter sequence schema.
     *
     * @return bool true if has alter sequence schema, False otherwise
     */
    public function hasAlterSequenceSchema()
    {
        return true;
    }

    /**
     * Determines if it has alter sequence start.
     *
     * @return bool true if has alter sequence start, False otherwise
     */
    public function hasAlterSequenceStart()
    {
        return true;
    }

    /**
     * Determines if it has alter table schema.
     *
     * @return bool true if has alter table schema, False otherwise
     */
    public function hasAlterTableSchema()
    {
        return true;
    }

    /**
     * Determines if it has autovacuum.
     *
     * @return bool true if has autovacuum, False otherwise
     */
    public function hasAutovacuum()
    {
        return true;
    }

    /**
     * Determines if it has create table like.
     *
     * @return bool true if has create table like, False otherwise
     */
    public function hasCreateTableLike()
    {
        return true;
    }

    /**
     * Determines if it has disable triggers.
     *
     * @return bool true if has disable triggers, False otherwise
     */
    public function hasDisableTriggers()
    {
        return true;
    }

    /**
     * Determines if it has alter domains.
     *
     * @return bool true if has alter domains, False otherwise
     */
    public function hasAlterDomains()
    {
        return true;
    }

    /**
     * Determines if it has enum types.
     *
     * @return bool true if has enum types, False otherwise
     */
    public function hasEnumTypes()
    {
        return true;
    }

    /**
     * Determines if it has fts.
     *
     * @return bool true if has fts, False otherwise
     */
    public function hasFTS()
    {
        return true;
    }

    /**
     * Determines if it has function costing.
     *
     * @return bool true if has function costing, False otherwise
     */
    public function hasFunctionCosting()
    {
        return true;
    }

    /**
     * Determines if it has function guc.
     *
     * @return bool true if has function guc, False otherwise
     */
    public function hasFunctionGUC()
    {
        return true;
    }

    /**
     * Determines if it has named parameters.
     *
     * @return bool true if has named parameters, False otherwise
     */
    public function hasNamedParams()
    {
        return true;
    }

    /**
     * Determines if it has prepare.
     *
     * @return bool true if has prepare, False otherwise
     */
    public function hasPrepare()
    {
        return true;
    }

    /**
     * Determines if it has prepared xacts.
     *
     * @return bool true if has prepared xacts, False otherwise
     */
    public function hasPreparedXacts()
    {
        return true;
    }

    /**
     * Determines if it has recluster.
     *
     * @return bool true if has recluster, False otherwise
     */
    public function hasRecluster()
    {
        return true;
    }

    /**
     * Determines if it has server admin funcs.
     *
     * @return bool true if has server admin funcs, False otherwise
     */
    public function hasServerAdminFuncs()
    {
        return true;
    }

    /**
     * Determines if it has query cancel.
     *
     * @return bool true if has query cancel, False otherwise
     */
    public function hasQueryCancel()
    {
        return true;
    }

    /**
     * Determines if it has user rename.
     *
     * @return bool true if has user rename, False otherwise
     */
    public function hasUserRename()
    {
        return true;
    }

    /**
     * Determines if it has user signals.
     *
     * @return bool true if has user signals, False otherwise
     */
    public function hasUserSignals()
    {
        return true;
    }

    /**
     * Determines if it has virtual transaction identifier.
     *
     * @return bool true if has virtual transaction identifier, False otherwise
     */
    public function hasVirtualTransactionId()
    {
        return true;
    }

    /**
     * Determines if it has alter database.
     *
     * @return bool true if has alter database, False otherwise
     */
    public function hasAlterDatabase()
    {
        return $this->hasAlterDatabaseRename();
    }

    /**
     * Determines if it has alter database rename.
     *
     * @return bool true if has alter database rename, False otherwise
     */
    public function hasAlterDatabaseRename()
    {
        return true;
    }

    /**
     * Determines if it has database collation.
     *
     * @return bool true if has database collation, False otherwise
     */
    public function hasDatabaseCollation()
    {
        return true;
    }

    /**
     * Determines if it has magic types.
     *
     * @return bool true if has magic types, False otherwise
     */
    public function hasMagicTypes()
    {
        return true;
    }

    /**
     * Determines if it has query kill.
     *
     * @return bool true if has query kill, False otherwise
     */
    public function hasQueryKill()
    {
        return true;
    }

    /**
     * Determines if it has concurrent index build.
     *
     * @return bool true if has concurrent index build, False otherwise
     */
    public function hasConcurrentIndexBuild()
    {
        return true;
    }

    /**
     * Determines if it has force reindex.
     *
     * @return bool true if has force reindex, False otherwise
     */
    public function hasForceReindex()
    {
        return false;
    }

    /**
     * Determines if it has bytea hexadecimal default.
     *
     * @return bool true if has bytea hexadecimal default, False otherwise
     */
    public function hasByteaHexDefault()
    {
        return true;
    }
}
