<?php

/**
 * PHPPgAdmin v6.0.0-beta.50
 */

namespace PHPPgAdmin\Traits;

/**
 * @file
 * A trait with helpers methods to debug, halt the app and format text to html
 */

/**
 * A trait with helpers methods to debug, halt the app and format text to html.
 *
 * @package PHPPgAdmin
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
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $btarray0 = ([
                'class2'    => $backtrace[1]['class'],
                'type2'     => $backtrace[1]['type'],
                'function2' => $backtrace[1]['function'],
                'spacer4'   => ' ',
                'line2'     => $backtrace[0]['line'],
            ]);

            $key = implode('', $btarray0);
        }

        $this->container->flash->addMessage($key, $content);
    }

    /**
     * Receives N parameters and sends them to the console adding where was it called from.
     */
    public function prtrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = ([
            /*'class0'    => $backtrace[3]['class'],
            'type0'     => $backtrace[3]['type'],
            'function0' => $backtrace[3]['function'],
            'spacer0'   => ' ',
            'line0'     => $backtrace[2]['line'],

            'spacer1'   => PHP_EOL,

            'class1'    => $backtrace[2]['class'],
            'type1'     => $backtrace[2]['type'],
            'function1' => $backtrace[2]['function'],
            'spacer2'   => ' ',
            'line1'     => $backtrace[1]['line'],

            'spacer3'   => PHP_EOL,*/

            'class2'    => $backtrace[1]['class'],
            'type2'     => $backtrace[1]['type'],
            'function2' => $backtrace[1]['function'],
            'spacer4'   => ' ',
            'line2'     => $backtrace[0]['line'],
        ]);

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);
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

        if ($set->RecordCount() <= 0) {
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

    /**
     * Receives N parameters and sends them to the console adding where was it
     * called from.
     */
    public static function statictrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = [
            'class'    => $backtrace[1]['class'],
            'type'     => $backtrace[1]['type'],
            'function' => $backtrace[1]['function'],
            'spacer'   => ' ',
            'line'     => $backtrace[0]['line'],
        ];

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);
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

    public function dump()
    {
        call_user_func_array(['Kint', 'dump'], func_get_args());
    }

    public function dumpAndDie()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);

        $folder = dirname(dirname(__DIR__));
        $file   = str_replace($folder, '', $backtrace[0]['file']);
        $line   = $backtrace[0]['line'];

        call_user_func_array('\Kint::dump', func_get_args());
        $this->halt('stopped by user at '.$file.' line '.$line);
    }
}
