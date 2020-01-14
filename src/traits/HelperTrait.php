<?php

/**
 * PHPPgAdmin v6.0.0-RC2
 */

namespace PHPPgAdmin\Traits;

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
    public function halt($msg = 'An error has happened')
    {
        $body = $this->container->responseobj->getBody();
        $body->write($msg);

        throw new \Slim\Exception\SlimException($this->container->requestobj, $this->container->responseobj);
    }

    /**
     * Adds a flash message to the session that will be displayed on the next request.
     *
     * @param mixed  $content msg content (can be object, array, etc)
     * @param string $key     The key to associate with the message. Defaults to the stack
     *                        trace of the closure or method that called addFlassh
     */
    public function addFlash($content, $key = '')
    {
        if ($key === '') {
            $key = self::getBackTrace();
        }
        // $this->dump(__METHOD__ . ': addMessage ' . $key . '  ' . json_encode($content));
        $this->container->flash->addMessage($key, $content);
    }

    public static function getBackTrace($offset = 0)
    {
        $i0        = $offset;
        $i1        = $offset + 1;
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $offset + 3);

        $btarray0 = ([
            'class'    => $backtrace[$i1]['class'] === 'Closure' ?
            $backtrace[$i0]['file'] :
            $backtrace[$i1]['class'],

            'type'     => $backtrace[$i1]['type'],

            'function' => $backtrace[$i1]['function'] === '{closure}'
            ? $backtrace[$i0]['function'] :
            $backtrace[$i1]['function'],

            'spacer4'  => ' ',
            'line'     => $backtrace[$i0]['line'],
        ]);

        return $btarray0;
        //dump($backtrace);
    }

    /**
     * Converts an ADORecordSet to an array.
     *
     * @param \PHPPgAdmin\ADORecordSet $set   The set
     * @param string                   $field optionally the field to query for
     *
     * @return array the parsed array
     */
    public static function recordSetToArray($set, $field = '')
    {
        $result = [];

        if ($set->recordCount() <= 0) {
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
        if ($set === true) {
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
        if (!isset($array[$key]) && $set === true) {
            $array[$key] = $default;
        }

        return $array;
    }

    public static function formatSizeUnits($bytes, $lang)
    {
        if ($bytes == -1) {
            $bytes = $lang['strnoaccess'];
        } elseif ($bytes >= 1099511627776) {
            $bytes = sprintf('%s %s', number_format($bytes / 1099511627776, 0), $lang['strtb']);
        } elseif ($bytes >= 1073741824) {
            $bytes = sprintf('%s %s', number_format($bytes / 1073741824, 0), $lang['strgb']);
        } elseif ($bytes >= 1048576) {
            $bytes = sprintf('%s %s', number_format($bytes / 1048576, 0), $lang['strmb']);
        } elseif ($bytes >= 1024) {
            $bytes = sprintf('%s %s', number_format($bytes / 1024, 0), $lang['strkb']);
        } else {
            $bytes = sprintf('%s %s', $bytes, $lang['strbytes']);
        }

        return $bytes;
    }

    /**
     * Returns a string with html <br> variant replaced with a new line.
     *
     * @param string $msg message to parse (<br> separated)
     *
     * @return string parsed message (linebreak separated)
     */
    public static function br2ln($msg)
    {
        return str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $msg);
    }

    /**
     * Receives N parameters and sends them to the console adding where was it called from.
     */
    public function prtrace(...$args)
    {
        return self::staticTrace($args);
    }

    public function dump(...$args)
    {
        return self::staticTrace($args);
    }

    /**
     * Receives N parameters and sends them to the console adding where was it
     * called from.
     *
     * @param mixed $variablesToDump
     * @param mixed $exitAfterwards
     */
    private static function staticTrace(
        $variablesToDump = [],
        string $whoCalledMe = '',
        $exitAfterwards = false
    ) {
        if (!$variablesToDump) {
            $variablesToDump = func_get_args();
        }
        if ($whoCalledMe === '') {
            $whoCalledMe = str_replace(BASE_PATH, '', implode('', self::getBackTrace(2)));
        }
        if ($exitAfterwards && function_exists('dd')) {
            dd([
                'args' => $variablesToDump,
                'from' => $whoCalledMe,
            ]);
        } elseif (function_exists('dump')) {
            dump([
                'args' => $variablesToDump,
                'from' => $whoCalledMe,
            ]);
        }
    }

    public function dumpAndDie(...$args)
    {
        return self::staticTrace($args);
    }
}
