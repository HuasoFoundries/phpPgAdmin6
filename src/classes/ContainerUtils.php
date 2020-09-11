<?php

/**
 * PHPPgAdmin 6.0.0
 */

namespace PHPPgAdmin;

use Slim\App;
use Slim\Container;

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
     * @var\Psr\Container\ContainerInterface
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
            'subfolder' => self::SUBFOLDER,
            'debug' => self::DEBUGMODE,

            // Configuration file version.  If this is greater than that in config.inc.php, then
            // the app will refuse to run.  This and $conf['version'] should be incremented whenever
            // backwards incompatible changes are made to config.inc.php-dist.
            'base_version' => 61,
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
     * @return\Psr\Container\ContainerInterface the container instance
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
      */
    public static function getInstance():self
    {
        if (!$_instance=self::$_instance) {
            self::$_instance = new self();
            $_instance=self::$_instance;
        }

        return $_instance;
    }

    /**
     * Creates a container.
     *
     * @param array $conf The conf
     *
     * @return \Slim\App ( description_of_the_return_value )
     */
    public static function createApp($conf)
    {
        $_instance = self::getInstance();

        $_instance
            ->withConf($conf)
            ->setExtra()
            ->setMisc()
            ->setViews();

        //ddd($container->subfolder);
        return $_instance->_app;
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
        $url = $this->container->misc->getLastTabURL($subject) ?? ['url' => 'alldb', 'urlvars' => ['subject' => 'server']];
        $destinationurl = $this->getRedirectUrl();

        if (!isset($_server_info['username'])) {
            return $destinationurl;
        }

        if (!\is_array($url)) {
            return $this->getRedirectUrl($subject);
        }
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

        return \str_replace('views/?', "views/{$subject}?", $destinationurl);
    }

    /**
     * Adds an error to the errors array property of the container.
     *
     * @param string $errormsg The error msg
     *
     * @return\Slim\Container The app container
     */
    public function addError(string $errormsg): \Slim\Container
    {
        //dump($errormsg);
        $errors = $this->container->get('errors');
        $errors[] = $errormsg;
        $this->container->offsetSet('errors', $errors);

        return $this->container;
    }

    /**
     * @param array $conf
     */
    private function withConf($conf): self
    {
        $container = self::getContainerInstance();
        $conf['plugins'] = [];

        $container['conf'] = static function (\Slim\Container $c) use ($conf): array {
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
         * @return \PHPPgAdmin\ViewManager
         */
        $container['view'] = static function (\Slim\Container $c): \PHPPgAdmin\ViewManager {
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
        /**
         * @return \PHPPgAdmin\Misc
         */
        $container['misc'] = static function (\Slim\Container $c): \PHPPgAdmin\Misc {
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
        $container['flash'] = static function (): \Slim\Flash\Messages {
            return new \Slim\Flash\Messages();
        };

        $container['lang'] = static function (\Slim\Container $c): array {
            $translations = new \PHPPgAdmin\Translations($c);

            return $translations->lang;
        };

        return $this;
    }
}
