<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin;

/**
 * @file
 * Handles Exceptions on ADODb
 */

/**
 * Released under both BSD license and Lesser GPL library license. Whenever
 * there is any discrepancy between the two licenses, the BSD license will take
 * precedence.
 * Set tabs to 4 for best viewing.
 * Latest version is available at http://php.weblogs.com
 * Exception-handling code using PHP5 exceptions (try-catch-throw).
 *
 * @package PHPPgAdmin
 *
 * @author John Lim
 * @copyright 2000-2013 John Lim <jlim@natsoft.com>
 * @copyright 2014      Damien Regad, Mark Newnham and the ADOdb community
 * @license MIT
 *
 * @version   Release: 5.20.9
 */
class ADOdbException extends \Exception
{
    public $dbms;
    public $fn;
    public $sql      = '';
    public $params   = '';
    public $host     = '';
    public $database = '';

    /**
     * Default Error Handler. This will be called with the following params.
     *
     * @param string $dbms           the RDBMS you are connecting to
     * @param string $fn             the name of the calling function (in uppercase)
     * @param number $errno          the native error number from the database
     * @param string $errmsg         the native error msg from the database
     * @param string $p1             $fn specific parameter - see below
     * @param string $p2             parameter 2
     * @param mixed  $thisConnection connection
     *
     * @throws \Exception
     */
    public function __construct($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection)
    {
        switch ($fn) {
            case 'EXECUTE':
                $this->sql    = is_array($p1) ? $p1[0] : $p1;
                $this->params = $p2;
                $s            = "${dbms} error: [${errno}: ${errmsg}] in ${fn}(\"{$this->sql}\")";

                break;
            case 'PCONNECT':
            case 'CONNECT':
                $user = $thisConnection->user;
                $s    = "${dbms} error: [${errno}: ${errmsg}] in ${fn}(${p1}, '${user}', '****', ${p2})";

                break;
            default:
                $s = "${dbms} error: [${errno}: ${errmsg}] in ${fn}(${p1}, ${p2})";

                break;
        }

        $this->dbms = $dbms;
        if ($thisConnection) {
            $this->host     = $thisConnection->host;
            $this->database = $thisConnection->database;
        }
        $this->fn  = $fn;
        $this->msg = $errmsg;

        if (!is_numeric($errno)) {
            $errno = -1;
        }

        parent::__construct($s, $errno);
    }

    /**
     * Default Error Handler. This will be called with the following params.
     *
     * @param string $dbms           the RDBMS you are connecting to
     * @param string $fn             the name of the calling function (in uppercase)
     * @param number $errno          the native error number from the database
     * @param string $errmsg         the native error msg from the database
     * @param string $p1             $fn specific parameter - see below
     * @param string $p2             parameter 2
     * @param mixed  $thisConnection connection
     *
     * @throws \PHPPgAdmin\ADOdbException
     *
     * @internal param $P2 $fn specific parameter - see below
     */
    public static function adodb_throw($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection)
    {
        if (error_reporting() == 0) {
            return;
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = [
            'msg'      => 'ADOdbException at ',
            'class'    => $backtrace[1]['class'],
            'type'     => $backtrace[1]['type'],
            'function' => $backtrace[1]['function'],
            'spacer'   => ' ',
            'line'     => $backtrace[0]['line'],
        ];

        $errmsg = htmlentities(\PHPPgAdmin\Traits\HelperTrait::br2ln($errmsg), ENT_NOQUOTES);
        $p1     = htmlentities(\PHPPgAdmin\Traits\HelperTrait::br2ln($p1), ENT_NOQUOTES);
        $p2     = htmlentities(\PHPPgAdmin\Traits\HelperTrait::br2ln($p2), ENT_NOQUOTES);

        switch ($fn) {
            case 'EXECUTE':
                $sql = str_replace(
                    [
                        'SELECT',
                        'WHERE',
                        'GROUP BY',
                        'FROM',
                        'HAVING',
                        'LIMIT',
                    ],
                    ["\nSELECT", "\nWHERE", "\nGROUP BY", "\nFROM", "\nHAVING", "\nLIMIT"],
                    $p1
                );

                $inputparams = $p2;

                $error_msg = '<p><b>strsqlerror</b><br />'.nl2br($errmsg).'</p> <p><b>SQL:</b><br />'.nl2br($sql).'</p> ';

                echo '<table class="error" cellpadding="5"><tr><td>'.nl2br($error_msg).'</td></tr></table><br />'."\n";

                break;
            case 'PCONNECT':
            case 'CONNECT':
                // do nothing;
                break;
            default:
                $s = "${dbms} error: [${errno}: ${errmsg}] in ${fn}(${p1}, ${p2})\n";
                echo "<table class=\"error\" cellpadding=\"5\"><tr><td>{$s}</td></tr></table><br />\n";

                break;
        }

        $tag = implode('', $btarray0);

        \PC::debug(['errno' => $errno, 'fn' => $fn, 'errmsg' => $errmsg], $tag);

        throw new \PHPPgAdmin\ADOdbException($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection);
    }
}
