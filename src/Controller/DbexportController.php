<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Controller;

/**
 * Base controller class.
 */
class DbexportController extends BaseController
{
    /**
     * Default method to render the controller according to the action parameter.
     */
    public function render()
    {
        $data = $this->misc->getDatabaseAccessor();

        // Prevent timeouts on large exports
        \set_time_limit(0);

        $response = $this
            ->container
            ->response;

        // Include application functions
        $f_schema = $f_object = '';
        $this->setNoOutput(true);

        \ini_set('memory_limit', '768M');
        $request = requestInstance();
        // Are we doing a cluster-wide dump or just a per-database dump
        $dumpall = ('server' === $request->getParam('subject'));

        // Check that database dumps are enabled.
        if (!$this->misc->isDumpEnabled($dumpall)) {
            return $response;
        }
        $server_info = $this->misc->getServerInfo();
        $config_entry = $dumpall ? 'pg_dumpall_path' : 'pg_dump_path';
        $dump_path = $server_info[$config_entry] ?? '';

        if ('' === $dump_path) {
            $this->halt(\sprintf("Your config file doesn\'t have an entry for '%s'", $config_entry));
        }
        // Get the path of the pg_dump/pg_dumpall executable
        $exe = $this->misc->escapeShellCmd($dump_path);

        // Obtain the pg_dump version number and check if the path is good
        $version = [];
        \preg_match('/(\\d+(?:\\.\\d+)?)(?:\\.\\d+)?.*$/', \exec($exe . ' --version'), $version);

        if (empty($version)) {
            if ($dumpall) {
                \printf($this->lang['strbadpgdumpallpath'], $server_info['pg_dumpall_path'] ?? '');
            } else {
                \printf($this->lang['strbadpgdumppath'], $server_info['pg_dump_path'] ?? '');
            }

            return;
        }

        $response = $response
            ->withHeader('Controller', $this->controller_name);

        $this->prtrace('REQUEST[output]', $request->getParam('output'));
        // Make it do a download, if necessary
        switch ($request->getParam('output')) {
            case 'show':
                \header('Content-Type: text/plain');
                $response = $response
                    ->withHeader('Content-type', 'text/plain');

                break;
            case 'download':
                // Set headers.  MSIE is totally broken for SSL downloading, so
                // we need to have it download in-place as plain text
                if (\mb_strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && isset($_SERVER['HTTPS'])) {
                    \header('Content-Type: text/plain');
                    $response = $response
                        ->withHeader('Content-type', 'text/plain');
                } else {
                    $response = $response
                        ->withHeader('Content-type', 'application/download')
                        ->withHeader('Content-Disposition', 'attachment; filename=dump.sql');
                }

                break;
            case 'gzipped':
                // MSIE in SSL mode cannot do this - it should never get to this point
                $response = $response
                    ->withHeader('Content-type', 'application/download')
                    ->withHeader('Content-Disposition', 'attachment; filename=dump.sql.gz');

                break;
        }

        // Set environmental variables that pg_dump uses
        \putenv('PGPASSWORD=' . ($server_info['password'] ?? ''));
        \putenv('PGUSER=' . ($server_info['username'] ?? ''));
        $hostname = $server_info['host'] ?? '';

        if (null !== $hostname && '' !== $hostname) {
            \putenv('PGHOST=' . $hostname);
        }
        $port = $server_info['port'] ?? 5432;

        if (null !== $port && '' !== $port) {
            \putenv('PGPORT=' . $port);
        }
        $cmd = $exe;
        // Build command for executing pg_dump.  '-i' means ignore version differences.
        // deprecated
        /*if (((float) $version[1]) < 9.5) {
        $this->prtrace('version', $version);

        $cmd = $exe . ' -i';
        } else {
        $cmd = $exe;
        }*/

        // we are PG 7.4+, so we always have a schema
        if (null !== $request->getParam('schema')) {
            $f_schema = $request->getParam('schema') ?? '';
            $data->fieldClean($f_schema);
        }
        $subject = $request->getParam('subject');
        // Check for a specified table/view
        switch ($subject) {
            case 'schema':
                // This currently works for 8.2+ (due to the orthoganl -t -n issue introduced then)
                $cmd .= ' -n ' . $this->misc->escapeShellArg(\sprintf(
                    '"%s"',
                    $f_schema
                ));

                break;
            case 'table':
            case 'view':
            case 'matview':
                $f_object = $request->getParam($subject);
                $this->prtrace('f_object', $f_object);
                $data->fieldClean($f_object);

                // Starting in 8.2, -n and -t are orthagonal, so we now schema qualify
                // the table name in the -t argument and quote both identifiers
                if (8.2 <= ((float) $version[1])) {
                    $cmd .= ' -t ' . $this->misc->escapeShellArg(\sprintf(
                        '"%s"."%s"',
                        $f_schema,
                        $f_object
                    ));
                } else {
                    // If we are 7.4 or higher, assume they are using 7.4 pg_dump and
                    // set dump schema as well.  Also, mixed case dumping has been fixed
                    // then..
                    $cmd .= ' -t ' . $this->misc->escapeShellArg($f_object)
                        . ' -n ' . $this->misc->escapeShellArg($f_schema);
                }
        }

        // Check for GZIP compression specified
        if ('gzipped' === $request->getParam('output') && !$dumpall) {
            $cmd .= ' -Z 9';
        }

        switch ($request->getParam('what')) {
            case 'dataonly':
                $cmd .= ' -a';

                if ('sql' === $request->getParam('d_format')) {
                    $cmd .= ' --inserts';
                } elseif (null !== $request->getParam('d_oids')) {
                    $cmd .= ' -o';
                }

                break;
            case 'structureonly':
                $cmd .= ' -s';

                if (null !== $request->getParam('s_clean')) {
                    $cmd .= ' -c';
                }

                break;
            case 'structureanddata':
                if ('sql' === $request->getParam('sd_format')) {
                    $cmd .= ' --inserts';
                } elseif (null !== $request->getParam('sd_oids')) {
                    $cmd .= ' -o';
                }

                if (null !== $request->getParam('sd_clean')) {
                    $cmd .= ' -c';
                }

                break;
        }

        if (!$dumpall) {
            \putenv('PGDATABASE=' . $request->getParam('database'));
        } else {
            $cmd .= $request->getParam('no_role_info') ? ' --no-role-password' : '';
            \putenv('PGDATABASE');
        }

        /*$this->prtrace(
            'ENV VARS',
            [
                'PGUSER' => \getenv('PGUSER'),
                'PGPASSWORD' => \getenv('PGPASSWORD'),
                'PGHOST' => \getenv('PGHOST'),
                'PGPORT' => \getenv('PGPORT'),
                'PGDATABASE' => \getenv('PGDATABASE'),
            ]
        );*/
        echo '/*';
        \printf(' %s', $cmd);
        \printf('*/');
        // Execute command and return the output to the screen
        \passthru($cmd);

        return $response;
    }
}
