<?php

/**
 * PHPPgAdmin6
 */

namespace PHPPgAdmin\Core;

use ArrayAccess;
use PHPPgAdmin\Decorators\Decorator;
use PHPPgAdmin\Traits\HelperTrait;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Collection;
use Slim\Container;
use Slim\DefaultServicesProvider;
use Slim\Flash\Messages;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @property string $BASE_PATH
 * @property string $database
 * @property bool $DEBUGMODE
 * @property array $deploy_info
 * @property Messages $flash
 * @property bool $IN_TEST
 * @property Misc $misc
 * @property Request $request
 * @property Response $response
 * @property string  $schema
 * @property string  $server
 * @property string $subFolder
 * @property string $THEME_PATH
 * @property ViewManager $view
 *
 * @method mixed get($varname='')
 */
class ContainerUtils extends Container implements ContainerInterface
{
    use HelperTrait;

    /**
     * @var null|self
     */
    private static $instance;

    /**
     * $appInstance.
     *
     * @var null|App
     */
    private static $appInstance;

    /**
     * Default settings.
     *
     * @var array<array-key, mixed>
     */
    private $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'routerCacheFile' => false,
    ];

    /**
     * Undocumented variable.
     *
     * @var array<array-key, mixed>
     */
    private static $envConfig = [
        'BASE_PATH' => '',
        'subFolder' => '',
        'DEBUGMODE' => false,
        'THEME_PATH' => '',
    ];

    /**
     * @param array<array-key, mixed> $values the parameters or objects
     */
    final public function __construct(array $values = [])
    {
        parent::__construct($values);

        $userSettings = $values['settings'] ?? [];
        $this->registerDefaultServices($userSettings);
        self::$instance = $this;
    }

    /**
     * Gets the subfolder.
     *
     * @param string $path The path
     *
     * @return string the subfolder
     */
    public function getSubfolder(string $path = ''): string
    {
        return \implode(\DIRECTORY_SEPARATOR, [$this->subFolder, $path]);
    }

    public static function getAppInstance(array $config = []): App
    {
        $config = \array_merge(self::getDefaultConfig($config['debugmode'] ?? false), $config);

        $container = self::getContainerInstance($config);

        if (null === self::$appInstance) {
            self::$appInstance = new App($container);
        }

        return self::$appInstance;
    }

    public static function getContainerInstance(array $config = []): self
    {
        self::$envConfig = [
            'msg' => '',
            'appThemes' => [
                'default' => 'Default',
                'cappuccino' => 'Cappuccino',
                'gotar' => 'Blue/Green',
                'bootstrap' => 'Bootstrap3',
            ],
            'display_sizes' => ['schemas' => false, 'tables' => false],
            'BASE_PATH' => $config['BASE_PATH'] ?? \dirname(__DIR__, 2),
            'subFolder' => $config['subfolder'] ?? '',
            'debug' => $config['debugmode'] ?? false,
            'THEME_PATH' => $config['theme_path'] ?? \dirname(__DIR__, 2) . '/assets/themes',
            'IN_TEST' => $config['IN_TEST'] ?? false,
            'webdbLastTab' => [],
        ];

        self::$envConfig = \array_merge(self::$envConfig, $config);

        if (null === self::$instance) {
            self::$instance = new static(self::$envConfig);

            self::$instance
                ->withConf(self::$envConfig);

            $handlers = new ContainerHandlers(self::$instance);
            $handlers->setExtra()
                ->setMisc()
                ->setViews()
                ->storeMainRequestParams()
                ->setHaltHandler();
        }

        //ddd($container->subfolder);
        return self::$instance;
    }

    /**
     * Determines the redirection url according to query string.
     *
     * @return string the redirect url
     */
    public function getRedirectUrl()
    {
        $container = self::getContainerInstance();
        $query_string = $container->request->getUri()->getQuery();

        // if server_id isn't set, then you will be redirected to intro
        if (null === $container->request->getQueryParam('server')) {
            $destinationurl = $this->subFolder . '/intro';
        } else {
            // otherwise, you'll be redirected to the login page for that server;
            $destinationurl = $this->subFolder . '/login' . ('' !== $query_string ? '?' . $query_string : '');
        }
        // ddd($destinationurl);
        return (\mb_strpos($destinationurl, '/') === 0) ? $destinationurl : '/' . $destinationurl;
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
        $container = self::getContainerInstance();
        // $this->dump(__METHOD__ . ': addMessage ' . $key . '  ' . json_encode($content));
        if ($container->flash) {
            $container->flash->addMessage($key, $content);
        }
    }

    /**
     * @param mixed $subject
     *
     * @return null|\PHPPgAdmin\Decorators\ActionUrlDecorator
     */
    public function getActionUrl($subject, bool $debug = false)
    {
        $container = self::getContainerInstance();
        $_server_info = $container->misc->getServerInfo();
        $url = $container->misc->getLastTabURL($subject) ?? ['url' => 'alldb', 'urlvars' => ['subject' => 'server']];
        $url['urlvars'] = \array_merge([], $url['urlvars'] ?? []);

        if (isset($_server_info['username']) && \is_array($url)) {
            $this->addFlash($url, 'getLastTabURL for ' . $subject);
            // Load query vars into superglobal arrays

            $urlvars = [];

            foreach ($url['urlvars'] as $key => $urlvar) {
                //$this->prtrace($key, $urlvar);
                $urlvars[$key] = Decorator::get_sanitized_value($urlvar, $_REQUEST);
            }
            $_REQUEST = \array_merge($_REQUEST, $urlvars);
            $_GET = \array_merge($_GET, $urlvars);
            //kdump($actionurl);
            return Decorator::actionurl($url['url'], $_GET);
        }
        //kdump($url);
        return null;
    }

    /**
     * Gets the destination with the last active tab selected for that controller
     * Usually used after going through a redirect route.
     *
     * @param string                  $subject The subject, usually a view name like 'server' or 'table'
     * @param array<array-key, mixed> $urlvars
     *
     * @return string The destination url with last tab set in the query string
     */
    public function getDestinationWithLastTab($subject, $urlvars = [])
    {
        $container = self::getContainerInstance();
        $_server_info = $container->misc->getServerInfo();
        $this->addFlash($subject, 'getDestinationWithLastTab');
        //$this->prtrace('$_server_info', $_server_info);
        // If username isn't set in server_info, you should login
        $url = $container->misc->getLastTabURL($subject) ?? ['url' => 'alldb', 'urlvars' => ['subject' => 'server']];
        $url['urlvars'] = \array_merge($urlvars, $url['urlvars'] ?? []);
        $destinationurl = $this->getRedirectUrl();

        if (isset($_server_info['username']) && \is_array($url)) {
            $this->addFlash($url, 'getLastTabURL for ' . $subject);
            // Load query vars into superglobal arrays

            $urlvars = [];

            foreach ($url['urlvars'] as $key => $urlvar) {
                //$this->prtrace($key, $urlvar);
                $urlvars[$key] = Decorator::get_sanitized_value($urlvar, $_REQUEST);
            }
            $_REQUEST = \array_merge($_REQUEST, $urlvars);
            $_GET = \array_merge($_GET, $urlvars);

            $actionurl = Decorator::actionurl($url['url'], $_GET);
            $destinationurl = \str_replace($this->subFolder, '', $actionurl->value($_GET));
        }

        return (('' === $container->subFolder || \mb_strpos($destinationurl, $container->subFolder) === 0) ? '' : $container->subFolder) . $destinationurl;
    }

    /**
     * Adds an error to the errors array property of the container.
     *
     * @param string $errormsg The error msg
     *
     * @return self The app container
     */
    public function addError(string $errormsg): Container
    {
        $container = self::getContainerInstance();
        $errors = $container->get('errors');
        $errors[] = $errormsg;
        $container->offsetSet('errors', $errors);

        return $container;
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
        return \str_replace(['<br>', '<br/>', '<br />'], \PHP_EOL, $msg);
    }

    /**
     * @return (bool|string)[][]
     *
     * @psalm-return array{settings: array{displayErrorDetails: bool, determineRouteBeforeAppMiddleware: true, base_path: string, debug: bool, phpMinVer: string, addContentLengthHeader: false, appName: string}}
     */
    public static function getDefaultConfig(bool $debug = false): array
    {
        return [
            'settings' => [
                'displayErrorDetails' => $debug,
                'determineRouteBeforeAppMiddleware' => true,
                'base_path' => \dirname(__DIR__, 2),
                'debug' => $debug,
                'phpMinVer' => '7.2', // PHP minimum version
                'addContentLengthHeader' => false,
                'appName' => 'PHPPgAdmin6',
            ],
        ];
    }

    public function getAllRequestVars(): array
    {
        return \array_merge(
            $this->request->getQueryParams() ?? [],
            $this->request->getParsedBody() ?? []
        );
    }

    /**
     * @param array<array-key, mixed> $conf
     *
     * @return static
     */
    private function withConf($conf): self
    {
        $container = self::getContainerInstance();
        $conf['plugins'] = [];

        $container->BASE_PATH = $conf['BASE_PATH'];
        $container->subFolder = $conf['subfolder'] ?? $conf['subFolder'];
        $container->debug = $conf['debugmode'];
        $container->THEME_PATH = $conf['theme_path'];
        $container->IN_TEST = $conf['IN_TEST'];
        $container['errors'] = [];
        $container['conf'] = /**
         * @return (bool[]|mixed|string)[]
         *
         * @psalm-return array{plugins: array<empty, empty>, display_sizes: array{schemas: bool, tables: bool}, theme: mixed|string}
         */
        static function (Container $c) use ($conf): array {
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
            //self::$envConfig=[
            //'BASE_PATH'=>$conf['BASE_PATH'],
            //'subFolder'=>$conf['subfolder'],
            //'DEBUGMODE'=>$conf['debugmode'],
            //'THEME_PATH'=>$conf['theme_path'],
            //'IN_TEST'=>$conf['IN_TEST']
            //];

            return $conf;
        };

        $container->subFolder = $conf['subfolder'];

        return $this;
    }

    /**
     * This function registers the default services that Slim needs to work.
     *
     * All services are shared, they are registered such that the
     * same instance is returned on subsequent calls.
     *
     * @param array<array-key, mixed> $userSettings Associative array of application settings
     */
    private function registerDefaultServices($userSettings): void
    {
        $defaultSettings = $this->defaultSettings;

        /**
         * This service MUST return an array or an instance of ArrayAccess.
         *
         * @return array|ArrayAccess
         */
        $this['settings'] = static function () use ($userSettings, $defaultSettings): Collection {
            return new Collection(\array_merge($defaultSettings, $userSettings));
        };

        $defaultProvider = new DefaultServicesProvider();
        $defaultProvider->register($this);
    }
}
