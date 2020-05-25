<?php

/**
 * PHPPgAdmin 6.0.1
 */

namespace PHPPgAdmin;

use Psr\Container\ContainerInterface;
use Slim\App;

\defined('BASE_PATH') || \define('BASE_PATH', \dirname(__DIR__, 2));
\defined('THEME_PATH') || \define('THEME_PATH', BASE_PATH . '/assets/themes');

\defined('DEBUGMODE') || \define('DEBUGMODE', false);
\defined('IN_TEST') || \define('IN_TEST', false);

/**
 * A class that adds convenience methods to the container.
 */
class ContainerUtils
{
    use \PHPPgAdmin\Traits\HelperTrait;
    /**
     * @var string
     */
    const BASE_PATH = BASE_PATH;
    /**
     * @var string
     */
    const SUBFOLDER = PHPPGA_SUBFOLDER;
    /**
     * @var string
     */
    const DEBUGMODE = DEBUGMODE;

    /**
     * @var string
     */
    const THEME_PATH = THEME_PATH;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var App
     */
    protected $_app;

    /**
     * @var array
     */
    protected $conf;

    /**
     * @var self
     */
    protected static $_instance;

    /**
     * Constructor of the ContainerUtils class.
     */
    public function __construct()
    {
        $composerinfo = \json_decode(\file_get_contents(BASE_PATH . '/composer.json'));
        $appVersion = $composerinfo->extra->version;

        $phpMinVer = (\str_replace(['<', '>', '='], '', $composerinfo->require->php));
        //$this->prtrace($appVersion);
        //$this->dump($composerinfo);
        $settings = [
            'determineRouteBeforeAppMiddleware' => true,
            'base_path' => self::BASE_PATH,
            'debug' => self::DEBUGMODE,

            // Configuration file version.  If this is greater than that in config.inc.php, then
            // the app will refuse to run.  This and $conf['version'] should be incremented whenever
            // backwards incompatible changes are made to config.inc.php-dist.
            'base_version' => 60,
            // Application version
            'appVersion' => 'v' . $appVersion,
            // Application name
            'appName' => 'phpPgAdmin6',

            // PostgreSQL and PHP minimum version
            'postgresqlMinVer' => '9.3',
            'phpMinVer' => $phpMinVer,
            'displayErrorDetails' => self::DEBUGMODE,
            'addContentLengthHeader' => false,
        ];

        if (!self::DEBUGMODE && !IN_TEST) {
            $settings['routerCacheFile'] = self::BASE_PATH . '/temp/route.cache.php';
        }
        $config = [
            'msg' => '',
            'appThemes' => [
                'default' => 'Default',
                'cappuccino' => 'Cappuccino',
                'gotar' => 'Blue/Green',
                'bootstrap' => 'Bootstrap3',
            ],
            'settings' => $settings,
        ];

        $this->_app = new App($config);

        // Fetch DI Container
        $container = $this->_app->getContainer();
        $container['utils'] = $this;
        $container['version'] = 'v' . $appVersion;
        $container['errors'] = [];
        $container['requestobj'] = $container['request'];
        $container['responseobj'] = $container['response'];
        $this->container = $container;
    }

    /**
     * Gets the container instance.
     *
     * @throws \Exception (description)
     *
     * @return ContainerInterface the container instance
     */
    public static function getContainerInstance()
    {
        $_instance = self::getInstance();

        if (!$container = $_instance->container) {
            throw new \Exception('Could not get a container');
        }

        return $container;
    }

    /**
     * Gets the instance.
     *
     * @return self the instance
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Creates a container.
     *
     * @param array $conf The conf
     *
     * @return [ContainerInterface,App] ( description_of_the_return_value )
     */
    public static function createContainer($conf)
    {
        $_instance = self::getInstance();

        $_instance
            ->setConf($conf)
            ->setExtra()
            ->setMisc()
            ->setViews();

        //ddd($container->subfolder);
        return [$_instance->container, self::$_instance->_app];
    }

    public function maybeRenderIframes($response, $subject, $query_string)
    {
        $c = self::getContainerInstance();

        $in_test = $c->view->offsetGet('in_test');

        if ('1' === $in_test) {
            $className = '\PHPPgAdmin\Controller\\' . \ucfirst($subject) . 'Controller';
            $controller = new $className($c);

            return $controller->render();
        }

        $viewVars = [
            'url' => '/src/views/' . $subject . ($query_string ? '?' . $query_string : ''),
            'headertemplate' => 'header.twig',
        ];

        return $c->view->render($response, 'iframe_view.twig', $viewVars);
    }

    /**
     * Gets the theme from
     * 1. The $_REQUEST global (when it's chosen from start screen)
     * 2. Server specific config theme 3.- $_SESSION global (subsequent requests after 1.) 4.- $_COOKIE global (mostly
     *    fallback for $_SESSION after 1.- and 3.-) 5.- theme as set in config 6.- 'default' theme.
     *
     * @param array      $conf         The conf
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
        if (\array_key_exists('theme', $_REQUEST) &&
            \array_key_exists($_REQUEST['theme'], $themefolders)) {
            $_theme = $_REQUEST['theme'];
        } elseif ( // otherwise, see if there's a theme associated with this particular server
            null !== $_server_info &&
            \array_key_exists('theme', $_server_info) &&
            \is_string($_server_info['theme']) &&
            \array_key_exists($_COOKIE['ppaTheme'], $themefolders)) {
            $_theme = $_server_info['theme'];
        } elseif (\array_key_exists('ppaTheme', $_SESSION) &&
            \array_key_exists($_SESSION['ppaTheme'], $themefolders)) {
            // otherwise check $_SESSION
            $_theme = $_SESSION['ppaTheme'];
        } elseif (\array_key_exists('ppaTheme', $_SESSION) &&
            \array_key_exists($_COOKIE['ppaTheme'], $themefolders)) {
            // oterwise check $_COOKIE
            $_theme = $_COOKIE['ppaTheme'];
        } elseif ( // see if there's a valid theme set in config file
            \array_key_exists('theme', $conf) &&
            \is_string($conf['theme']) &&
            \array_key_exists($conf['theme'], $themefolders)) {
            $_theme = $conf['theme'];
        } else {
            // okay then, use default theme
            $_theme = 'default';
        }

        return $_theme;
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
        if (null === $this->container->requestobj->getQueryParam('server')) {
            $destinationurl = self::SUBFOLDER . '/src/views/intro';
        } else {
            // otherwise, you'll be redirected to the login page for that server;
            $destinationurl = self::SUBFOLDER . '/src/views/login' . ($query_string ? '?' . $query_string : '');
        }

        return $destinationurl;
    }

    /**
     * Adds a flash message to the session that will be displayed on the next request.
     *
     * @param mixed  $content msg content (can be object, array, etc)
     * @param string $key     The key to associate with the message. Defaults to the stack
     *                        trace of the closure or method that called addFlassh
     */
    public function addFlash($content, $key = ''): void
    {
        if ('' === $key) {
            $key = self::getBackTrace();
        }
        // $this->dump(__METHOD__ . ': addMessage ' . $key . '  ' . json_encode($content));
        if ($this->container->flash) {
            $this->container->flash->addMessage($key, $content);
        }
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
            $this->addFlash($url, 'getLastTabURL for ' . $subject);
            // Load query vars into superglobal arrays
            if (isset($url['urlvars'])) {
                $urlvars = [];

                foreach ($url['urlvars'] as $key => $urlvar) {
                    //$this->prtrace($key, $urlvar);
                    $urlvars[$key] = \PHPPgAdmin\Decorators\Decorator::get_sanitized_value($urlvar, $_REQUEST);
                }
                $_REQUEST = \array_merge($_REQUEST, $urlvars);
                $_GET = \array_merge($_GET, $urlvars);
            }

            $actionurl = \PHPPgAdmin\Decorators\Decorator::actionurl($url['url'], $_GET);
            $destinationurl = $actionurl->value($_GET);
        }
        $destinationurl = \str_replace('views/?', "views/{$subject}?", $destinationurl);
        // $this->prtrace('destinationurl for ' . $subject, $destinationurl);
        return $destinationurl;
    }

    /**
     * Adds an error to the errors array property of the container.
     *
     * @param string $errormsg The error msg
     *
     * @return ContainerInterface The app container
     */
    public function addError(string $errormsg): ContainerInterface
    {
        $errors = $this->container->get('errors');
        $errors[] = $errormsg;
        $this->container->offsetSet('errors', $errors);

        return $this->container;
    }

    private function setConf($conf)
    {
        $container = self::getContainerInstance();
        $conf['plugins'] = [];

        $container['conf'] = static function ($c) use ($conf) {
            $display_sizes = $conf['display_sizes'];

            if (\is_array($display_sizes)) {
                $conf['display_sizes'] = [
                    'schemas' => (bool) isset($display_sizes['schemas']) && true === $display_sizes['schemas'],
                    'tables' => (bool) isset($display_sizes['tables']) && true === $display_sizes['tables'],
                ];
            } else {
                $conf['display_sizes'] = [
                    'schemas' => (bool) $display_sizes,
                    'tables' => (bool) $display_sizes,
                ];
            }

            if (!isset($conf['theme'])) {
                $conf['theme'] = 'default';
            }

            foreach ($conf['servers'] as &$server) {
                if (!isset($server['port'])) {
                    $server['port'] = 5432;
                }

                if (!isset($server['sslmode'])) {
                    $server['sslmode'] = 'unspecified';
                }
            }

            return $conf;
        };

        $container->subfolder = self::SUBFOLDER;

        return $this;
    }

    /**
     * Sets the views.
     *
     * @return self ( description_of_the_return_value )
     */
    private function setViews()
    {
        $container = self::getContainerInstance();

        /**
         * return ViewManager.
         */
        $container['view'] = static function ($c) {
            $misc = $c->misc;
            $view = new ViewManager(BASE_PATH . '/assets/templates', [
                'cache' => BASE_PATH . '/temp/twigcache',
                'auto_reload' => $c->get('settings')['debug'],
                'debug' => $c->get('settings')['debug'],
            ], $c);

            $misc->setView($view);

            return $view;
        };

        return $this;
    }

    /**
     * Sets the instance of Misc class.
     *
     * @return self ( description_of_the_return_value )
     */
    private function setMisc()
    {
        $container = self::getContainerInstance();
        $container['misc'] = static function ($c) {
            $misc = new \PHPPgAdmin\Misc($c);

            $conf = $c->get('conf');

            // 4. Check for theme by server/db/user
            $_server_info = $misc->getServerInfo();

            /* starting with PostgreSQL 9.0, we can set the application name */
            if (isset($_server_info['pgVersion']) && 9 <= $_server_info['pgVersion']) {
                \putenv('PGAPPNAME=' . $c->get('settings')['appName'] . '_' . $c->get('settings')['appVersion']);
            }

            return $misc;
        };

        return $this;
    }

    private function setExtra()
    {
        $container = self::getContainerInstance();
        $container['flash'] = static function () {
            return new \Slim\Flash\Messages();
        };

        $container['lang'] = static function ($c) {
            $translations = new \PHPPgAdmin\Translations($c);

            return $translations->lang;
        };

        return $this;
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
        if (!$gestor = \opendir(self::THEME_PATH)) {
            \closedir($gestor);

            return [];
        }
        $themefolders = [];

        /* This is the right way to iterate on a folder */
        while (false !== ($foldername = \readdir($gestor))) {
            if ('.' === $foldername || '..' === $foldername) {
                continue;
            }

            $folderpath = \sprintf('%s%s%s', self::THEME_PATH, \DIRECTORY_SEPARATOR, $foldername);
            $stylesheet = \sprintf('%s%s%s', $folderpath, \DIRECTORY_SEPARATOR, 'global.css');
            // if $folderpath if indeed a folder and contains a global.css file, then it's a theme
            if (\is_dir($folderpath) &&
                \is_file($stylesheet)) {
                $themefolders[$foldername] = $folderpath;
            }
        }

        \closedir($gestor);

        return $themefolders;
    }
}
