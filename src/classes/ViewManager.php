<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin;

/**
 * @file
 * Class to hold various commonly used functions
 *
 * Id: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 */

/**
 * Class to hold various commonly used functions.
 *
 * Release: Misc.php,v 1.171 2008/03/17 21:35:48 ioguix Exp $
 */
class ViewManager extends \Slim\Views\Twig
{
    use \PHPPgAdmin\Traits\HelperTrait;

    /**
     * @var array
     */
    public $appLangFiles = [];

    /**
     * @var string
     */
    public $appName = '';

    /**
     * @var string
     */
    public $appVersion = '';

    /**
     * @var string
     */
    public $form = '';

    /**
     * @var string
     */
    public $href = '';

    /**
     * @var array
     */
    public $lang = [];

    /**
     * @var array
     */
    public $conf;

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public $phpMinVer;

    /**
     * @var string
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public $postgresqlMinVer;

    /**
     * @var \PHPPgAdmin\Misc
     */
    public $misc;

    /**
     * @var \PHPPgAdmin\ContainerUtils
     */
    protected $container;

    /**
     * Undocumented variable.
     *
     * @var array
     */
    private static $themeFolders = [];

    private $_connection;

    /**
     * @var bool
     */
    private $_no_db_connection = false;

    /**
     * @var bool
     */
    private $_reload_browser = false;

    private $_data;

    private $_database;

    /**
     * @var string
     */
    private $_server_id;

    private $_server_info;

    /**
     * @var string
     */
    private $_error_msg = '';

    /**
     * Undocumented variable.
     *
     * @var self
     */
    private static $instance;

    /**
     * @param mixed                      $path
     * @param mixed                      $settings
     * @param \PHPPgAdmin\ContainerUtils $c
     */
    public function __construct($path, $settings, \PHPPgAdmin\ContainerUtils $c)
    {
        $this->lang = $c->get('lang');
        $this->conf = $c->get('conf');
        $this->misc = $c->get('misc');
        parent::__construct($path, $settings);
        $this->container = $c;
        $environment = $c->get('environment');
        $base_script_trailing_str = \mb_substr($environment['SCRIPT_NAME'], 1);
        $request_basepath = $c['request']->getUri()->getBasePath();
        // Instantiate and add Slim specific extension
        $basePath = \rtrim(\str_ireplace($base_script_trailing_str, '', $request_basepath), '/');

        $this->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

        $this->offsetSet('subfolder', \containerInstance()->subFolder);
        $this->offsetSet('theme', $this->misc->getConf('theme'));
        $this->offsetSet('Favicon', $this->icon('Favicon'));
        $this->offsetSet('Introduction', $this->icon('Introduction'));
        $this->offsetSet('lang', $this->lang);

        $this->offsetSet('applangdir', $this->lang['applangdir']);

        $this->offsetSet('appName', $c->get('settings')['appName']);

        $_theme = $this->getTheme($this->conf, $this->misc->getServerInfo());

        // If a theme comes in the request, overwrite whatever theme was set to cookie and settion store
        if ($_request_theme = $this->getRequestTheme()) {
            $this->setCookieTheme($_request_theme);
            $this->setSessionTheme($_request_theme);
            $_theme = $_request_theme;
        }

        if (!$this->getSessionTheme() || !$this->getCookieTheme()) {
            // If there's no session theme, or cookie theme,
            // store the latest one we determined from request, session,cookie, conf or default
            /* save the selected theme in cookie for a year */
            \setcookie('ppaTheme', $_theme, \time() + 31536000, '/');
            $_SESSION['ppaTheme'] = $_theme;
        }
        $this->misc->setConf('theme', $_theme);
    }

    /**
     * Internally sets the reload browser property.
     *
     * @param bool $flag sets internal $_reload_browser var which will be passed to the footer methods
     *
     * @return \PHPPgAdmin\ViewManager this class instance
     */
    public function setReloadBrowser($flag): self
    {
        $this->_reload_browser = (bool) $flag;

        return $this;
    }

    /**
     * @return bool
     */
    public function getReloadBrowser(): bool
    {
        return $this->_reload_browser;
    }

    public function maybeRenderIframes(\Slim\Http\Response $response, string $subject, string $query_string): \Slim\Http\Response
    {
        $c = $this->getContainer();

        $in_test = $this->offsetGet('in_test');

        if ('1' === $in_test) {
            $className = self::getControllerClassName($subject);
            $controller = new $className($c);

            return $controller->render();
        }

        $viewVars = [
            'url' => '/src/views/' . $subject . ($query_string ? '?' . $query_string : ''),
            'headertemplate' => 'header.twig',
        ];

        return $this->render($response, 'iframe_view.twig', $viewVars);
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
        $_theme = 'default';
        // List of themes
        $themefolders = $this->getThemeFolders();

        // Check if theme is in $_REQUEST, $_SESSION or $_COOKIE
        // 1.- First priority: $_REQUEST, this happens when you use the selector
        if (\array_key_exists('theme', $_REQUEST) &&
            \array_key_exists($_REQUEST['theme'], $themefolders)
        ) {
            $_theme = $_REQUEST['theme'];
        } elseif ( // otherwise, see if there's a theme associated with this particular server
            null !== $_server_info &&
            \array_key_exists('theme', $_server_info) &&
            \is_string($_server_info['theme']) &&
            \array_key_exists($_COOKIE['ppaTheme'], $themefolders)
        ) {
            $_theme = $_server_info['theme'];
        } elseif (isset($_SESSION) && \array_key_exists('ppaTheme', $_SESSION) &&
            \array_key_exists($_SESSION['ppaTheme'], $themefolders)
        ) {
            // otherwise check $_SESSION
            $_theme = $_SESSION['ppaTheme'];
        } elseif (\array_key_exists('ppaTheme', $_COOKIE) &&
            \array_key_exists($_COOKIE['ppaTheme'], $themefolders)
        ) {
            // oterwise check $_COOKIE
            $_theme = $_COOKIE['ppaTheme'];
        } elseif ( // see if there's a valid theme set in config file
            \array_key_exists('theme', $conf) &&
            \is_string($conf['theme']) &&
            \array_key_exists($conf['theme'], $themefolders)
        ) {
            $_theme = $conf['theme'];
        }

        return $_theme;
    }

    /**
     * Sets the form tracking variable.
     */
    public function setForm(): string
    {
        $form = [];

        if ($this->container->server) {
            $form[] = \sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                'server',
                \htmlspecialchars($this->container->server)
            );
        }

        if ($this->container->database) {
            $form[] = \sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                'database',
                \htmlspecialchars($this->container->database)
            );
        }

        if ($this->container->schema) {
            $form[] = \sprintf(
                '<input type="hidden" name="%s" value="%s" />',
                'schema',
                \htmlspecialchars($this->container->schema)
            );
        }
        $this->form = \implode("\n", $form);

        return $this->form;
    }

    /**
     * Displays link to the context help.
     *
     * @param string $str      the string that the context help is related to (already escaped)
     * @param string $help     help section identifier
     * @param bool   $do_print true to echo, false to return
     *
     * @return string|void
     */
    public function printHelp($str, $help = null, $do_print = true)
    {
        if (null !== $help) {
            $helplink = $this->getHelpLink($help);
            $str .= '<a class="help" href="' . $helplink . '" title="' . $this->lang['strhelp'] . '" target="phppgadminhelp">';
            $str .= $this->lang['strhelpicon'] . '</a>';
        }

        if ($do_print) {
            echo $str;
        } else {
            return $str;
        }
    }

    /**
     * Gets the help link.
     *
     * @param string $help The help subject
     *
     * @return string the help link
     */
    public function getHelpLink($help)
    {
        return \htmlspecialchars(
            $this->container->getSubfolder('help?help=') .
                \urlencode($help) .
                '&server=' .
                \urlencode($this->misc->getServerId())
        );
    }

    /**
     * @param string $icon
     *
     * @return string
     */
    public function icon($icon = ''): string
    {
        $icon = (string) ($icon ?? '');

        $theme = $this->conf['theme'];
        $path = 'assets/images/themes';
        $default_icon = \sprintf('%s/%s/default/DisconnectedServer.png', \containerInstance()->subFolder, $path);

        if (\is_readable(\sprintf('%s/%s/%s/%s.png', \containerInstance()->BASE_PATH, $path, $theme, $icon))) {
            return \sprintf('%s/%s/%s/%s.png', \containerInstance()->subFolder, $path, $theme, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/%s/%s.gif', \containerInstance()->BASE_PATH, $path, $theme, $icon))) {
            return \sprintf('%s/%s/%s/%s.gif', \containerInstance()->subFolder, $path, $theme, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/%s/%s.ico', \containerInstance()->BASE_PATH, $path, $theme, $icon))) {
            return \sprintf('%s/%s/%s/%s.ico', \containerInstance()->subFolder, $path, $theme, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/default/%s.png', \containerInstance()->BASE_PATH, $path, $icon))) {
            return \sprintf('%s/%s/default/%s.png', \containerInstance()->subFolder, $path, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/default/%s.gif', \containerInstance()->BASE_PATH, $path, $icon))) {
            return \sprintf('%s/%s/default/%s.gif', \containerInstance()->subFolder, $path, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/default/%s.ico', \containerInstance()->BASE_PATH, $path, $icon))) {
            return \sprintf('%s/%s/default/%s.ico', \containerInstance()->subFolder, $path, $icon);
        }

        return $default_icon;
    }

    private function getCookieTheme(): ?string
    {
        $cookie_theme = $_COOKIE['ppaTheme'] ?? null;

        return $this->isThemeAvailable($cookie_theme) ? $cookie_theme : null;
    }

    private function getSessionTheme(): ?string
    {
        $session_theme = $_SESSION['ppaTheme'] ?? null;

        return $this->isThemeAvailable($session_theme) ? $session_theme : null;
    }

    private function getRequestTheme(): ?string
    {
        $request_theme = $_REQUEST['theme'] ?? null;

        return $this->isThemeAvailable($request_theme) ? $request_theme : null;
    }

    private function isThemeAvailable(?string $_theme = null): bool
    {
        return \array_key_exists($_theme, $this->getThemeFolders());
    }

    private function setCookieTheme(string $_theme): void
    {
        if ($this->isThemeAvailable($_theme)) {
            \setcookie('ppaTheme', $_theme, \time() + 31536000, '/');
        }
    }

    private function setSessionTheme(string $_theme): void
    {
        if ($this->isThemeAvailable($_theme)) {
            $_SESSION['ppaTheme'] = $_theme;
        }
    }

    /**
     * Undocumented function.
     *
     * @param string $subject
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     *
     * @return class-string
     */
    private static function getControllerClassName(string $subject): string
    {
        return '\PHPPgAdmin\Controller\\' . \ucfirst($subject) . 'Controller';
    }

    private function getContainer(): \PHPPgAdmin\ContainerUtils
    {
        return $this->container;
    }

    /**
     * Traverse THEME_PATH, consider as theme folders those which
     * contain a `global.css` stylesheet.
     *
     * @return array the theme folders
     */
    private function getThemeFolders(): array
    {
        if (!empty(self::$themeFolders)) {
            return self::$themeFolders;
        }
        // no THEME_PATH (how?) then return empty array
        if (!$gestor = \opendir(containerInstance()->THEME_PATH)) {
            \closedir($gestor);

            return [];
        }
        $themefolders = [];

        /* This is the right way to iterate on a folder */
        while (false !== ($foldername = \readdir($gestor))) {
            if ('.' === $foldername || '..' === $foldername) {
                continue;
            }

            $folderpath = \sprintf('%s%s%s', \containerInstance()->THEME_PATH, \DIRECTORY_SEPARATOR, $foldername);
            $stylesheet = \sprintf('%s%s%s', $folderpath, \DIRECTORY_SEPARATOR, 'global.css');
            // if $folderpath if indeed a folder and contains a global.css file, then it's a theme
            if (\is_dir($folderpath) &&
                \is_file($stylesheet)
            ) {
                $themefolders[$foldername] = $folderpath;
            }
        }

        \closedir($gestor);
        self::$themeFolders = $themefolders;

        return $themefolders;
    }
}
