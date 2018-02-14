<?php
namespace PHPPgAdmin;

trait HelperTrait
{
    public function halt($msg)
    {
        $body = $this->container->responseobj->getBody();
        $body->write($msg);

        throw new \Slim\Exception\SlimException($this->container->requestobj, $this->container->responseobj);
    }

    /**
     * Receives N parameters and sends them to the console adding where was it called from
     * @return [type] [description]
     */
    public function prtrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

        $btarray0 = ([
            'class0'    => $backtrace[3]['class'],
            'type0'     => $backtrace[3]['type'],
            'function0' => $backtrace[3]['function'],
            'spacer0'   => ' ',
            'line0'     => $backtrace[2]['line'],

            'spacer1'   => "\n",

            'class1'    => $backtrace[2]['class'],
            'type1'     => $backtrace[2]['type'],
            'function1' => $backtrace[2]['function'],
            'spacer2'   => ' ',
            'line1'     => $backtrace[1]['line'],

            'spacer3'   => "\n",

            'class2'    => $backtrace[1]['class'],
            'type2'     => $backtrace[1]['type'],
            'function2' => $backtrace[1]['function'],
            'spacer4'   => ' ',
            'line2'     => $backtrace[0]['line'],
        ]);

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);
    }

    public static function statictrace()
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $btarray0 = ([
            'class'    => $backtrace[1]['class'],
            'type'     => $backtrace[1]['type'],
            'function' => $backtrace[1]['function'],
            'spacer'   => ' ',
            'line'     => $backtrace[0]['line'],
        ]);

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);
    }

    /**
     * Returns a string with html <br> variant replaced with a new line
     * @param  string $msg [description]
     * @return string      [description]
     */
    public static function br2ln(string $msg)
    {
        return str_replace(['<br>', '<br/>', '<br />'], "\n", $msg);
    }
}
