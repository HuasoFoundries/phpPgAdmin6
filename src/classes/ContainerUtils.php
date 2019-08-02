<?php

/**
 * PHPPgAdmin v6.0.0-beta.52
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
    use \PHPPgAdmin\Traits\HelperTrait;

    protected $container;

    /**
     * Constructor of the ContainerUtils class.
     *
     * @param \Slim\Container $container The app container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Determines the redirection url according to query string.
     *
     * @return string the redirect url
     */
    public function getRedirectUrl()
    {
        $query_string = $this->container->requestobj->getUri()->getQuery();

        // if server_id isn't set, then you will be redirected to intro
        if ($this->container->requestobj->getQueryParam('server') === null) {
            $destinationurl = \SUBFOLDER.'/src/views/intro';
        } else {
            // otherwise, you'll be redirected to the login page for that server;
            $destinationurl = \SUBFOLDER.'/src/views/login'.($query_string ? '?'.$query_string : '');
        }

        return $destinationurl;
    }

    /**
     * Gets the destination with the last active tab selected for that controller
     * Usually used after going through a redirect route.
     *
     * @param string $subject The subject, usually a view name like 'server' or 'table'
     *
     * @return string The destination url with last tab set in the query string
     */
    public function getDestinationWithLastTab($subject)
    {
        $_server_info = $this->container->misc->getServerInfo();
        $this->addFlash($subject, 'getDestinationWithLastTab');
        //$this->prtrace('$_server_info', $_server_info);
        // If username isn't set in server_info, you should login
        if (!isset($_server_info['username'])) {
            $destinationurl = $this->getRedirectUrl();
        } else {
            $url = $this->container->misc->getLastTabURL($subject);
            $this->addFlash($url, 'getLastTabURL for '.$subject);
            // Load query vars into superglobal arrays
            if (isset($url['urlvars'])) {
                $urlvars = [];
                foreach ($url['urlvars'] as $key => $urlvar) {
                    //$this->prtrace($key, $urlvar);
                    $urlvars[$key] = \PHPPgAdmin\Decorators\Decorator::get_sanitized_value($urlvar, $_REQUEST);
                }
                $_REQUEST = array_merge($_REQUEST, $urlvars);
                $_GET     = array_merge($_GET, $urlvars);
            }

            $actionurl      = \PHPPgAdmin\Decorators\Decorator::actionurl($url['url'], $_GET);
            $destinationurl = $actionurl->value($_GET);
        }
        $destinationurl = str_replace('views/?', "views/{$subject}?", $destinationurl);
        //$this->prtrace('destinationurl for ' . $subject, $destinationurl);
        return $destinationurl;
    }

    /**
     * Adds an error to the errors array property of the container.
     *
     * @param string $errormsg The error msg
     *
     * @return \Slim\Container The app container
     */
    public function addError($errormsg)
    {
        $errors   = $this->container->get('errors');
        $errors[] = $errormsg;
        $this->container->offsetSet('errors', $errors);

        return $this->container;
    }
}
