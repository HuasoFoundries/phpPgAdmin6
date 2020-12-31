<?php

/**
 * PHPPgAdmin 6.1.3
 */

use Symfony\Component\Yaml\Yaml;

$conf = [
    /**
     * Change this parameter ONLY if you mean to serve phpPgAdmin from a subfolder
     * e.g for `https://www.server.com/phppga`  this parameter should be `/phppga`
     * (leading slash, no trailing slash).
     *
     * If you mean to serve phpPgAdmin6 in the document root (e.g `https://www.server.com`)
     * this parameter must be an empty string
     */
    'subfolder' => '',

    /**
     * enable this to calculate schema and table sizes. This will have a performance impact
     * e.g. $conf['display_sizes'] = true.
     *
     * you can also enable it specifically for tables and or schemas:
     * $conf['display_sizes'] = ['schemas'=>false,'tables'=>true];
     */
    'display_sizes' => false,

    /**
     * Set to true if you want to enable debugging output.
     */
    'debugmode' => false,

    /**
     * Don't touch this value. It's used to inform the config structure has a breaking change.
     */
    'version' => 61,
];

// Two debug library examples. Pick one of course.
$conf['register_debuggers'] = static function (): void {
    //require_once __DIR__ . '/.configs/debug.kint.php';
    //require_once __DIR__ . '/.configs/debug.var_dumper.php';
};

// optionally, set a path for your error log, relative to this project root
// $conf['error_log']='temp/phppga.php_error.log';

// An example server.  Create as many of these as you wish,
// indexed from zero upwards.
$server_index = 0;

/**
 * $conf['servers'] is an array that holds (at least) one server block.
 *
 * @see https://github.com/HuasoFoundries/phpPgAdmin6/wiki/Config:-servers
 */
$conf['servers'][$server_index] = [
    // Display name for the server on the login screen
    'desc' => 'PostgreSQL',

    // Hostname or IP address for server.  Use '' for UNIX domain socket.
    // use 'localhost' for TCP/IP connection on this computer
    'host' => '',

    // Database port on server (5432 is the PostgreSQL default)
    'port' => 5432,

    // Database SSL mode
    // Possible options: disable, allow, prefer, require
    // To require SSL on older servers use option: legacy
    // To ignore the SSL mode, use option: unspecified
    'sslmode' => 'allow',

    // Change the default database only if you cannot connect to template1.
    // For a PostgreSQL 8.1+ server, you can set this to 'postgres'.
    'defaultdb' => 'template1',

    // Specify the path to the database dump utilities for this server.
    // You can set these to '' if no dumper is available.
    'pg_dump_path' => '/usr/bin/pg_dump',
    'pg_dumpall_path' => '/usr/bin/pg_dumpall',
];

// Server group 0 will show up with an alias
$conf['srv_groups'][0]['desc'] = 'dev1 and prod2';
// Add here servers indexes belonging to the group '0' seperated by comma
$conf['srv_groups'][0]['servers'] = '1,3';

// Default language. E.g.: 'english', 'polish', etc.  See lang/ directory
// for all possibilities. If you specify 'auto' (the default) it will use
// your browser preference.
$conf['default_lang'] = 'auto';

// AutoComplete uses AJAX interaction to list foreign key values
// on insert fields. It currently only works on single column
// foreign keys. You can choose one of the following values:
// 'default on' enables AutoComplete and turns it on by default.
// 'default off' enables AutoComplete but turns it off by default.
// 'disable' disables AutoComplete.
$conf['autocomplete'] = 'default on';

// If extra login security is true, then logins via phpPgAdmin with no
// password or certain usernames (pgsql, postgres, root, administrator)
// will be denied. Only set this false once you have read the FAQ and
// understand how to change PostgreSQL's pg_hba.conf to enable
// passworded local connections.
$conf['extra_login_security'] = true;

// Only show owned databases?
// Note: This will simply hide other databases in the list - this does
// not in any way prevent your users from seeing other database by
// other means. (e.g. Run 'SELECT * FROM pg_database' in the SQL area.)
$conf['owned_only'] = false;

// Display comments on objects?  Comments are a good way of documenting
// a database, but they do take up space in the interface.
$conf['show_comments'] = true;

// Display "advanced" objects? Setting this to true will show
// aggregates, types, operators, operator classes, conversions,
// languages and casts in phpPgAdmin. These objects are rarely
// administered and can clutter the interface.
$conf['show_advanced'] = false;

// Display "system" objects?
$conf['show_system'] = false;

// Minimum length users can set their password to.
$conf['min_password_length'] = 1;

// Width of the left frame in pixels (object browser)
$conf['left_width'] = 200;

// Which look & feel theme to use
$conf['theme'] = 'default';

// Show OIDs when browsing tables?
$conf['show_oids'] = false;

// Max rows to show on a page when browsing record sets
$conf['max_rows'] = 30;

// Max chars of each field to display by default in browse mode
$conf['max_chars'] = 50;

// Send XHTML strict headers?
$conf['use_xhtml_strict'] = false;

// Base URL for PostgreSQL documentation.
// '%s', if present, will be replaced with the PostgreSQL version
// (e.g. 8.4 )
$conf['help_base'] = 'http://www.postgresql.org/docs/%s/interactive/';

// Configuration for ajax scripts
// Time in seconds. If set to 0, refreshing data using ajax will be disabled (locks and activity pages)
$conf['ajax_refresh'] = 3;

// If there's a config.yml in the root folder, parse it and merge its contents with $conf array
// see config.example.yml
$yamlConfigPath = \implode(\DIRECTORY_SEPARATOR, [__DIR__, 'config.yml']);

if (\is_readable($yamlConfigPath) && \class_exists('Symfony\Component\Yaml\Yaml')) {
    try {
        $yamlConfig = Yaml::parseFile($yamlConfigPath);
        // Servers and srv_groups must be merged beforehand
        $servers = $conf['servers'] ?? [];

        foreach ($yamlConfig['servers'] ?? [] as $index => $srv) {
            $servers[] = $srv;
        }
        $srv_groups = $conf['srv_groups'] ?? [];

        foreach ($yamlConfig['srv_groups'] ?? [] as $index => $srv_group) {
            $srv_groups[] = $srv;
        }

        $yamlConfig['srv_groups'] = \array_merge([
            $conf['srv_groups'] ?? [],
            $yamlConfig['srv_groups'] ?? [],
        ]);
        $conf = \array_merge($conf, $yamlConfig);

        $conf['servers'] = $servers ?? [];
        $conf['srv_groups'] = $srv_groups ?? [];
    } catch (\Exception $e) {
        die($e->getMessage());
        \error_log($e->getTraceAsString());
    }
}
