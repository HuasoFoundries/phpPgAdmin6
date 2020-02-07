<?php

/**
 * PHPPgAdmin v6.0.0-RC8.
 */

namespace PHPPgAdmin;

/**
 * @file
 * A class that adds convenience methods to the container
 */

/**
 * A class that adds convenience methods to the container.
 */
class ContainerUtils
{
    use \PHPPgAdmin\Traits\HelperTrait;

    protected $container;
    /** @var Connector */
    protected static $instance;

    /**
     * Constructor of the ContainerUtils class.
     *
     * @param \Slim\Container $container The app container
     */
    public function __construct()
    {
        $composerinfo = json_decode(file_get_contents(BASE_PATH.'/composer.json'));
        $appVersion = $composerinfo->version;

        $phpMinVer = (str_replace(['<', '>', '='], '', $composerinfo->require->php));
        //$this->prtrace($appVersion);
        //$this->dump($composerinfo);

        $config = [
            'msg'       => '',
            'appThemes' => [
                'default'    => 'Default',
                'cappuccino' => 'Cappuccino',
                'gotar'      => 'Blue/Green',
                'bootstrap'  => 'Bootstrap3',
            ],
            'settings'  => [
                'displayErrorDetails'               => DEBUGMODE,
                'determineRouteBeforeAppMiddleware' => true,
                'base_path'                         => BASE_PATH,
                'debug'                             => DEBUGMODE,

                // 'routerCacheFile'                   => BASE_PATH . '/temp/route.cache.php',

                // Configuration file version.  If this is greater than that in config.inc.php, then
                // the app will refuse to run.  This and $conf['version'] should be incremented whenever
                // backwards incompatible changes are made to config.inc.php-dist.
                'base_version'                      => 60,
                // Application version
                'appVersion'                        => 'v'.$appVersion,
                // Application name
                'appName'                           => 'phpPgAdmin6',

                // PostgreSQL and PHP minimum version
                'postgresqlMinVer'                  => '9.3',
                'phpMinVer'                         => $phpMinVer,
                'displayErrorDetails'               => DEBUGMODE,
                'addContentLengthHeader'            => false,
            ],
        ];

        $this->app = new \Slim\App($config);

        // Fetch DI Container
        $container = $this->app->getContainer();
        $container['utils'] = $this;
        $container['version'] = 'v'.$appVersion;
        $container['errors'] = [];

        $this->container = $container;
    }

    public static function createContainer()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return [self::$instance->container, self::$instance->app];
    }

    public function maybeRenderIframes($response, $subject, $query_string)
    {
        $c = $this->container;
        $in_test = $c->view->offsetGet('in_test');

        if ($in_test === '1') {
            $className = '\PHPPgAdmin\Controller\\'.ucfirst($subject).'Controller';
            $controller = new $className($c);

            return $controller->render();
        }

        $viewVars = [
            'url'            => '/src/views/'.$subject.($query_string ? '?'.$query_string : ''),
            'headertemplate' => 'header.twig',
        ];

        return $c->view->render($response, 'iframe_view.twig', $viewVars);
    }

    /**
     * Gets the theme from
     * 1. The $_REQUEST global (when it's chosen from start screen)
     * 2. Server specific config theme
     * 3.- $_SESSION global (subsequent requests after 1.)
     * 4.- $_COOKIE global (mostly fallback for $_SESSION after 1.- and 3.-)
     * 5.- theme as set in config
     * 6.- 'default' theme.
     *
     * @param <type>     $conf         The conf
     * @param null|mixed $_server_info
     *
     * @return string the theme
     */
    public function getTheme(array $conf, $_server_info = null)
    {
        $_theme = null;
        // List of themes
        $themefolders = $this->getThemeFolders();
        // Check if theme is in $_REQUEST, $_SESSION or $_COOKIE
        // 1.- First priority: $_REQUEST, this happens when you use the selector
        if (array_key_exists('theme', $_REQUEST) &&
            array_key_exists($_REQUEST['theme'], $themefolders)) {
            $_theme = $_REQUEST['theme'];
        } elseif ( // otherwise, see if there's a theme associated with this particular server
            !is_null($_server_info) &&
            array_key_exists('theme', $_server_info) &&
            is_string($_server_info['theme']) &&
            array_key_exists($_COOKIE['ppaTheme'], $themefolders)) {
            $_theme = $_server_info['theme'];
        } elseif (array_key_exists('ppaTheme', $_SESSION) &&
            array_key_exists($_SESSION['ppaTheme'], $themefolders)) {
            // otherwise check $_SESSION
            $_theme = $_SESSION['ppaTheme'];
        } elseif (array_key_exists('ppaTheme', $_SESSION) &&
            array_key_exists($_COOKIE['ppaTheme'], $themefolders)) {
            // oterwise check $_COOKIE
            $_theme = $_COOKIE['ppaTheme'];
        } elseif ( // see if there's a valid theme set in config file
            array_key_exists('theme', $conf) &&
            is_string($conf['theme']) &&
            array_key_exists($conf['theme'], $themefolders)) {
            $_theme = $conf['theme'];
        } else {
            // okay then, use default theme
            $_theme = 'default';
        }

        return $_theme;
    }

    /**
     * Traverse THEME_PATH, consider as theme folders those which
     * contain a `global.css` stylesheet.
     *
     * @return array the theme folders
     */
    private function getThemeFolders()
    {
        // no THEME_PATH (how?) then return empty array
        if (!$gestor = opendir(THEME_PATH)) {
            closedir($gestor);

            return [];
        }
        $themefolders = [];

        /* This is the right way to iterate on a folder */
        while (false !== ($foldername = readdir($gestor))) {
            if ($foldername == '.' || $foldername == '..') {
                continue;
            }

            $folderpath = sprintf('%s%s%s', THEME_PATH, DIRECTORY_SEPARATOR, $foldername);
            $stylesheet = sprintf('%s%s%s', $folderpath, DIRECTORY_SEPARATOR, 'global.css');
            // if $folderpath if indeed a folder and contains a global.css file, then it's a theme
            if (is_dir($folderpath) &&
                is_file($stylesheet)) {
                $themefolders[$foldername] = $folderpath;
            }
        }

        closedir($gestor);

        return $themefolders;
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
                $_GET = array_merge($_GET, $urlvars);
            }

            $actionurl = \PHPPgAdmin\Decorators\Decorator::actionurl($url['url'], $_GET);
            $destinationurl = $actionurl->value($_GET);
        }
        $destinationurl = str_replace('views/?', "views/{$subject}?", $destinationurl);
        // $this->prtrace('destinationurl for ' . $subject, $destinationurl);
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
        $errors = $this->container->get('errors');
        $errors[] = $errormsg;
        $this->container->offsetSet('errors', $errors);

        return $this->container;
    }
}
