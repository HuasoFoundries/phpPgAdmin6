<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin\Traits;

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__, 2));

/**
 * @file
 * A trait with helpers methods to debug, halt the app and format text to html
 */

/**
 * A trait with helpers methods to debug, halt the app and format text to html.
 */
trait HelperTrait
{
    /**
     * Halts the execution of the program. It's like calling exit() but using builtin Slim Exceptions.
     *
     * @param string $msg The message to show to the user
     *
     * @throws \Slim\Exception\SlimException (description)
     */
    public function halt($msg = 'An error has happened'): void
    {
        $body = \responseInstance()->getBody();
        $body->write($msg);

        throw new \Slim\Exception\SlimException(\requestInstance(), \responseInstance());
    }

    public static function getBackTrace($offset = 0)
    {
        $i0 = $offset;
        $i1 = $offset + 1;
        $backtrace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, $offset + 3);

        return [
            'class' => 'Closure' === $backtrace[$i1]['class'] ?
            $backtrace[$i0]['file'] :
            $backtrace[$i1]['class'],

            'type' => $backtrace[$i1]['type'],

            'function' => '{closure}' === $backtrace[$i1]['function']
            ? $backtrace[$i0]['function'] :
            $backtrace[$i1]['function'],

            'spacer4' => ' ',
            'line' => $backtrace[$i0]['line'],
        ];
        //dump($backtrace);
    }

    /**
     * Converts an ADORecordSet to an array.
     *
     * @param \ADORecordSet $set   The set
     * @param string        $field optionally the field to query for
     *
     * @return array the parsed array
     */
    public static function recordSetToArray($set, $field = '')
    {
        $result = [];

        if (0 >= $set->recordCount()) {
            return $result;
        }

        while (!$set->EOF) {
            $result[] = $field ? $set->fields[$field] : $set;
            $set->moveNext();
        }

        return $result;
    }

    /**
     * Checks if a variable is defined, in which case assign its value to $var1
     * If it isn't and $set is true, assign the default value. Otherwise don't
     * assign anything to $var1.
     *
     * @param mixed $var1    The variable to manipulate if $set if true
     * @param mixed $var2    The value to assign to $var1 if it's defined
     * @param mixed $default The default value to set, it $set is true
     * @param bool  $set     True to set the default value if $var2 isn't defined
     *
     * @return mixed the value of $var2 is $var2 is set, or $default otherwise
     */
    public function setIfIsset(&$var1, $var2, $default = null, $set = true)
    {
        if (isset($var2)) {
            $var1 = $var2;

            return $var1;
        }

        if (true === $set) {
            $var1 = $default;

            return $var1;
        }

        return $default;
    }

    /**
     * Checks if the $key of an $array is set. If it isn't, optionally set it to
     * the default parameter.
     *
     * @param array      $array   The array to check
     * @param int|string $key     The key to check
     * @param mixed      $default The default value to set, it $set is true
     * @param bool       $set     True to set the default value if $key isn't
     *                            set
     *
     * @return array the original array
     */
    public function coalesceArr(&$array, $key, $default = null, $set = true)
    {
        if (!isset($array[$key]) && true === $set) {
            $array[$key] = $default;
        }

        return $array;
    }

    public static function formatSizeUnits($bytes, $lang)
    {
        if (-1 === $bytes) {
            $bytes = $lang['strnoaccess'];
        } elseif (1099511627776 <= $bytes) {
            $bytes = \sprintf('%s %s', \number_format($bytes / 1099511627776, 0), $lang['strtb']);
        } elseif (1073741824 <= $bytes) {
            $bytes = \sprintf('%s %s', \number_format($bytes / 1073741824, 0), $lang['strgb']);
        } elseif (1048576 <= $bytes) {
            $bytes = \sprintf('%s %s', \number_format($bytes / 1048576, 0), $lang['strmb']);
        } elseif (1024 <= $bytes) {
            $bytes = \sprintf('%s %s', \number_format($bytes / 1024, 0), $lang['strkb']);
        } else {
            $bytes = \sprintf('%s %s', $bytes, $lang['strbytes']);
        }

        return $bytes;
    }

    /**
     * Receives N parameters and sends them to the console adding where was it called from.
     *
     * @param array<int, mixed> $args
     */
    public function prtrace(...$args): void
    {
        if (\function_exists('\dump')) {
            \dump($args);
        }
    }

    /**
     * Just an alias of prtrace.
     *
     * @param array<int, mixed> $args The arguments
     */
    public function dump(...$args): void
    {
        if (\function_exists('\dump')) {
            \dump($args);
        }
    }

    /**
     * Just an alias of prtrace.
     *
     * @param array<int, mixed> $args The arguments
     */
    public function dumpAndDie(...$args): void
    {
        if (\function_exists('\dump')) {
            \dump($args);
        }

        exit();
    }

    /**
     * Receives N parameters and sends them to the console adding where was it
     * called from.
     *
     * @param array<int, mixed> $args
     */
    public static function staticTrace(
        ...$args
    ): void {
        if (\function_exists('\dump')) {
            \dump($args);
        }
    }
}
