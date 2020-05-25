<?php

/**
 * PHPPgAdmin v6.0.0-RC9
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
     * @var string
     */
    const BASE_PATH = ContainerUtils::BASE_PATH;

    /**
     * @var string
     */
    const THEME_PATH = ContainerUtils::THEME_PATH;
    /**
     * @var string
     */
    const SUBFOLDER = ContainerUtils::SUBFOLDER;
    /**
     * @var string
     */
    const DEBUGMODE = ContainerUtils::DEBUGMODE;

    public $appLangFiles = [];

    public $appName = '';

    public $appVersion = '';

    public $form = '';

    public $href = '';

    public $lang = [];

    public $conf;

    public $phpMinVer;

    public $postgresqlMinVer;

    public $view;

    protected $container;

    private $_connection;

    private $_no_db_connection = false;

    private $_reload_browser = false;

    private $_data;

    private $_database;

    private $_server_id;

    private $_server_info;

    private $_error_msg = '';

    private static $instance = null;

    /**
     * @param \Slim\Container $container The container
     * @param mixed           $path
     * @param mixed           $settings
     * @param \Slim\Container $c
     */
    public function __construct($path, $settings, \Slim\Container $c)
    {
        $this->lang = $c->get('lang');
        $this->conf = $c->get('conf');
        $this->misc = $c->get('misc');
        parent::__construct($path, $settings);
        $this->container          = $c;
        $environment              = $c->get('environment');
        $base_script_trailing_str = \mb_substr($environment['SCRIPT_NAME'], 1);
        $request_basepath         = $c['request']->getUri()->getBasePath();
        // Instantiate and add Slim specific extension
        $basePath = \rtrim(\str_ireplace($base_script_trailing_str, '', $request_basepath), '/');

        $this->addExtension(new \Slim\Views\TwigExtension($c['router'], $basePath));

        $this->offsetSet('subfolder', $c->subfolder);
        $this->offsetSet('theme', $this->misc->getConf('theme'));
        $this->offsetSet('Favicon', $this->icon('Favicon'));
        $this->offsetSet('Introduction', $this->icon('Introduction'));
        $this->offsetSet('lang', $this->lang);

        $this->offsetSet('applangdir', $this->lang['applangdir']);

        $this->offsetSet('appName', $c->get('settings')['appName']);

        $_theme = $this->getTheme($this->conf, $this->misc->getServerInfo());

        if (null !== $_theme && isset($_SESSION)) {
            /* save the selected theme in cookie for a year */
            @\setcookie('ppaTheme', $_theme, \time() + 31536000, '/');
            $_SESSION['ppaTheme'] = $_theme;
            $this->misc->setConf('theme', $_theme);
        }
    }

    public function maybeRenderIframes($response, $subject, $query_string)
    {
        $c = $this->getContainer();

        $in_test = $this->offsetGet('in_test');

        if ('1' === $in_test) {
            $className  = '\PHPPgAdmin\Controller\\' . \ucfirst($subject) . 'Controller';
            $controller = new $className($c);

            return $controller->render();
        }

        $viewVars = [
            'url'            => '/src/views/' . $subject . ($query_string ? '?' . $query_string : ''),
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
        } elseif (isset($_SESSION) && \array_key_exists('ppaTheme', $_SESSION) &&
            \array_key_exists($_SESSION['ppaTheme'], $themefolders)) {
            // otherwise check $_SESSION
            $_theme = $_SESSION['ppaTheme'];
        } elseif (\array_key_exists('ppaTheme', $_COOKIE) &&
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
     * Sets the form tracking variable.
     */
    public function setForm()
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
            $this->getSubfolder('help?help=') .
            \urlencode($help) .
            '&server=' .
            \urlencode($this->misc->getServerId())
        );
    }

    public function icon($icon)
    {
        if (!\is_string($icon)) {
            return '';
        }

        $theme        = $this->conf['theme'];
        $path         = 'assets/images/themes';
        $default_icon = \sprintf('%s/%s/default/DisconnectedServer.png', self::SUBFOLDER, $path);

        if (\is_readable(\sprintf('%s/%s/%s/%s.png', self::BASE_PATH, $path, $theme, $icon))) {
            return \sprintf('%s/%s/%s/%s.png', self::SUBFOLDER, $path, $theme, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/%s/%s.gif', self::BASE_PATH, $path, $theme, $icon))) {
            return \sprintf('%s/%s/%s/%s.gif', self::SUBFOLDER, $path, $theme, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/%s/%s.ico', self::BASE_PATH, $path, $theme, $icon))) {
            return \sprintf('%s/%s/%s/%s.ico', self::SUBFOLDER, $path, $theme, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/default/%s.png', self::BASE_PATH, $path, $icon))) {
            return \sprintf('%s/%s/default/%s.png', self::SUBFOLDER, $path, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/default/%s.gif', self::BASE_PATH, $path, $icon))) {
            return \sprintf('%s/%s/default/%s.gif', self::SUBFOLDER, $path, $icon);
        }

        if (\is_readable(\sprintf('%s/%s/default/%s.ico', self::BASE_PATH, $path, $icon))) {
            return \sprintf('%s/%s/default/%s.ico', self::SUBFOLDER, $path, $icon);
        }

        return $default_icon;
    }

    private function getContainer()
    {
        return $this->container;
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
