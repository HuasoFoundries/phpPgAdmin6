<?php

/**
 * PHPPgAdmin v6.0.0-beta.33
 */

namespace PHPPgAdmin;

/**
 * @file
 * A class that adds convenience methods to the container
 */

/**
 * A class that adds convenience methods to the container.
 *
 * @package PHPPgAdmin
 */
class ContainerUtils
{
    use \PHPPgAdmin\HelperTrait;

    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getRedirectUrl()
    {
        $query_string = $this->container->requestobj->getUri()->getQuery();

        // but if server_id isn't set, then you will be redirected to intro
        if ($this->container->requestobj->getQueryParam('server') === null) {
            $destinationurl = SUBFOLDER . '/src/views/intro';
        } else {
            $destinationurl = SUBFOLDER . '/src/views/login' . ($query_string ? '?' . $query_string : '');
        }

        return $destinationurl;
    }

    public function getDestinationWithLastTab($subject)
    {
        $_server_info = $this->container->misc->getServerInfo();

        // If username isn't set in server_info, you should login
        if (!isset($_server_info['username'])) {
            $destinationurl = $this->getRedirectUrl();
        } else {
            $url = $this->container->misc->getLastTabURL($subject);

            // Load query vars into superglobal arrays
            if (isset($url['urlvars'])) {
                $urlvars = [];
                foreach ($url['urlvars'] as $key => $urlvar) {
                    $urlvars[$key] = \PHPPgAdmin\Decorators\Decorator::get_sanitized_value($urlvar, $_REQUEST);
                }
                $_REQUEST = array_merge($_REQUEST, $urlvars);
                $_GET     = array_merge($_GET, $urlvars);
            }

            $actionurl      = \PHPPgAdmin\Decorators\Decorator::actionurl($url['url'], $_GET);
            $destinationurl = $actionurl->value($_GET);
        }

        return $destinationurl;
    }

    public function addError($errormsg)
    {
        $errors   = $this->container->get('errors');
        $errors[] = $errormsg;
        $this->container->offsetSet('errors', $errors);

        return $this->container;
    }

    /**
     * Receives N parameters and sends them to the console adding where was it called from.
     *
     * @return [type] [description]
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

            'spacer1'   => ' ',

            'class1'    => $backtrace[2]['class'],
            'type1'     => $backtrace[2]['type'],
            'function1' => $backtrace[2]['function'],
            'spacer2'   => ' ',
            'line1'     => $backtrace[1]['line'],

            'spacer3'   => ' ',*/

            'class2'    => $backtrace[1]['class'],
            'type2'     => $backtrace[1]['type'],
            'function2' => $backtrace[1]['function'],
            'spacer4'   => ' ',
            'line2'     => $backtrace[0]['line'],
        ]);

        $tag = implode('', $btarray0);

        \PC::debug(func_get_args(), $tag);

        return $this->container;
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

    public function dump()
    {
        call_user_func_array('\Kint::dump', func_get_args());
    }

    public function dump_and_die()
    {
        call_user_func_array('\Kint::dump', func_get_args());

        $body = new \Slim\Http\Body(fopen('php://temp', 'r+'));
        $body->write('terminating script');

        return $this->container->responseobj
                    ->withStatus(200)
                    ->withHeader('Content-type', 'text/html')
                    ->withBody($body);
    }

    /**
     * Returns a string with html <br> variant replaced with a new line.
     *
     * @param string $msg [description]
     *
     * @return string [description]
     */
    public static function br2ln(string $msg)
    {
        return str_replace(['<br>', '<br/>', '<br />'], "\n", $msg);
    }
}
