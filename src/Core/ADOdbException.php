<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Core;

use Exception;

/**
 * @file
 * Handles Exceptions on ADODb
 */
/**
 * Released under both BSD-3-CLAUSE license and GPL-2.0-OR-LATER. Whenever
 * there is any discrepancy between the two licenses, the BSD license will take
 * precedence.
 * Set tabs to 4 for best viewing.
 * Latest version is available at http://php.weblogs.com
 * Exception-handling code using PHP5 exceptions (try-catch-throw).
 *
 * @author John Lim
 * @copyright 2000-2013 John Lim <jlim@natsoft.com>
 * @copyright 2014      Damien Regad, Mark Newnham and the ADOdb community
 *
 * @version   Release: 5.20.9
 */
class ADOdbException extends Exception
{
    public $dbms;

    public $fn;

    /**
     * Undocumented variable.
     *
     * @var string
     */
    public $sql = '';

    /**
     * Undocumented variable.
     *
     * @var string
     */
    public $params = '';

    /**
     * Undocumented variable.
     *
     * @var string
     */
    public $host = '';

    public $database = '';

    /**
     * Undocumented variable.
     *
     * @var string
     */
    public $msg = '';

    /**
     * Default Error Handler. This will be called with the following params.
     *
     * @param string $dbms           the RDBMS you are connecting to
     * @param string $fn             the name of the calling function (in uppercase)
     * @param int    $errno          the native error number from the database
     * @param string $errmsg         the native error msg from the database
     * @param string $p1             $fn specific parameter - see below
     * @param string $p2             parameter 2
     * @param mixed  $thisConnection connection
     *
     * @throws Exception
     */
    public function __construct($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection)
    {
        switch ($fn) {
            case 'EXECUTE':
                $this->sql = \is_array($p1) ? $p1[0] : $p1;
                $this->params = $p2;
                $s = \sprintf(
                    '%s error: [%s: %s] in %s("%s")',
                    $dbms,
                    $errno,
                    $errmsg,
                    $fn,
                    $this->sql
                );

                break;
            case 'PCONNECT':
            case 'CONNECT':
                $user = $thisConnection->user;
                $s = \sprintf(
                    '%s error: [%s: %s] in %s(%s, \'%s\', \'****\', %s)',
                    $dbms,
                    $errno,
                    $errmsg,
                    $fn,
                    $p1,
                    $user,
                    $p2
                );

                break;

            default:
                $s = \sprintf(
                    '%s error: [%s: %s] in %s(%s, %s)',
                    $dbms,
                    $errno,
                    $errmsg,
                    $fn,
                    $p1,
                    $p2
                );

                break;
        }

        $this->dbms = $dbms;

        if ($thisConnection) {
            $this->host = $thisConnection->host;
            $this->database = $thisConnection->database;
        }
        $this->fn = $fn;
        $this->msg = $errmsg;

        if (!\is_numeric($errno)) {
            $errno = -1;
        }

        parent::__construct($s, $errno);
    }

    /**
     * Default Error Handler. This will be called with the following params.
     *
     * @param string $dbms           the RDBMS you are connecting to
     * @param string $fn             the name of the calling function (in uppercase)
     * @param int    $errno          the native error number from the database
     * @param string $errmsg         the native error msg from the database
     * @param string $p1             $fn specific parameter - see below
     * @param string $p2             parameter 2
     * @param mixed  $thisConnection connection
     *
     * @throws self
     *
     * @internal param $P2 $fn specific parameter - see below
     */
    public static function adodb_throw($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection): void
    {
        if (0 === \error_reporting()) {
            return;
        }

        $backtrace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = [
            'msg' => 'ADOdbException at ',
            'class' => $backtrace[1]['class'],
            'type' => $backtrace[1]['type'],
            'function' => $backtrace[1]['function'],
            'spacer' => ' ',
            'line' => $backtrace[0]['line'],
        ];
        $errmsg = \htmlentities(ContainerUtils::br2ln($errmsg), \ENT_NOQUOTES);
        $p1 = \htmlentities(ContainerUtils::br2ln($p1), \ENT_NOQUOTES);
        $p2 = \htmlentities(ContainerUtils::br2ln($p2), \ENT_NOQUOTES);

        $tag = \implode('', $btarray0);

        //\PC::debug(['errno' => $errno, 'fn' => $fn, 'errmsg' => $errmsg], $tag);

        $adoException = new self($dbms, $fn, $errno, $errmsg, $p1, $p2, $thisConnection);
        echo \sprintf(
            '<table class="error" cellpadding="5"><tr><td>%s</td></tr></table><br />
',
            $adoException->msg
        );

        // adodb_backtrace($adoException->getTrace());
        throw $adoException;
    }
}
