<?php

/**
 * PHPPgAdmin v6.0.0-beta.48
 */

namespace PHPPgAdmin;

/**
 * @file
 * A class that implements the plugin's system
 */

/**
 * A class that implements the plugin's system.
 *
 * @package PHPPgAdmin
 */
class PluginManager
{
    use \PHPPgAdmin\Traits\HelperTrait;
    /**
     * Attributes.
     */
    private $_plugins_list    = [];
    private $_available_hooks = [
        'head',
        'toplinks',
        'tabs',
        'trail',
        'navlinks',
        'actionbuttons',
        'tree',
        'logout',
    ];
    private $_actions = [];
    private $_hooks   = [];

    /**
     * Register the plugins.
     *
     * @param \Slim\Container $container
     *
     * @internal param $this ->language - Language that have been used
     *
     * @throws \Interop\Container\Exception\ContainerException
     * @throws \Slim\Exception\ContainerValueNotFoundException
     */
    public function __construct(\Slim\Container $container)
    {
        $this->language  = $container->has('language') ? $container->get('language') : 'english';
        $this->lang      = $container->get('lang');
        $this->conf      = $container->get('conf');
        $this->container = $container;
        if (!isset($this->conf['plugins'])) {
            return;
        }

        // Get the activated plugins
        $plugins = $this->conf['plugins'];

        foreach ($plugins as $activated_plugin) {
            $plugin_file = \BASE_PATH.'/src/plugins/'.$activated_plugin.'/plugin.php';

            // Verify is the activated plugin exists
            if (file_exists($plugin_file)) {
                include_once $plugin_file;

                try {
                    $plugin = new $activated_plugin($this->language);
                    $this->addPlugin($plugin);
                } catch (\Exception $e) {
                    continue;
                }
            } else {
                $this->halt(sprintf($this->lang['strpluginnotfound']."\t\n", $activated_plugin));
            }
        }
    }

    /**
     * Add a plugin in the list of plugins to manage.
     *
     * @param mixed $plugin - Instance from plugin
     */
    public function addPlugin($plugin)
    {
        //The $plugin_name is the identification of the plugin.
        //Example: PluginExample is the identification for PluginExample
        //It will be used to get a specific plugin from the _plugins_list.
        $plugin_name                       = $plugin->get_name();
        $this->_plugins_list[$plugin_name] = $plugin;

        //Register the plugin's functions
        $_hooks = $plugin->get__hooks();
        foreach ($_hooks as $hook => $functions) {
            if (!in_array($hook, $this->_available_hooks, true)) {
                $this->halt(sprintf($this->lang['strhooknotfound']."\t\n", $hook));
            }
            $this->_hooks[$hook][$plugin_name] = $functions;
        }

        //Register the plugin's _actions
        $_actions                     = $plugin->get__actions();
        $this->_actions[$plugin_name] = $_actions;
    }

    public function getPlugin($plugin)
    {
        if (isset($this->_plugins_list[$plugin])) {
            return $this->_plugins_list[$plugin];
        }

        return null;
    }

    /**
     * Execute the plugins hook functions when needed.
     *
     * @param string $hook          - The place where the function will be called
     * @param array  $function_args - An array reference with arguments to give to called function
     */
    public function doHook($hook, &$function_args)
    {
        //$this->prtrace('_hooks', $this->_hooks, $function_args);
        if (isset($this->_hooks[$hook])) {
            foreach ($this->_hooks[$hook] as $plugin_name => $functions) {
                $plugin = $this->_plugins_list[$plugin_name];
                foreach ($functions as $function) {
                    if (method_exists($plugin, $function)) {
                        call_user_func([$plugin, $function], $function_args);
                    }
                }
            }
        }
    }

    /**
     * Execute a plugin's action.
     *
     * @param string $plugin_name - The plugin name
     * @param string $action      - action that will be executed
     */
    public function do_action($plugin_name, $action)
    {
        if (!isset($this->_plugins_list[$plugin_name])) {
            // Show an error and stop the application
            $this->halt(sprintf($this->lang['strpluginnotfound']."\t\n", $plugin_name));
        }
        $plugin = $this->_plugins_list[$plugin_name];

        // Check if the plugin's method exists and if this method is an declared action.
        if (method_exists($plugin, $action) and in_array($action, $this->_actions[$plugin_name], true)) {
            call_user_func([$plugin, $action]);
        } else {
            // Show an error and stop the application
            $this->halt(sprintf($this->lang['stractionnotfound']."\t\n", $action, $plugin_name));
        }
    }
}
