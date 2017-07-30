<?php

namespace PHPPgAdmin;

/**
 * A class that implements the plugin's system
 */

class PluginManager
{

    /**
     * Attributes
     */
    private $plugins_list    = [];
    private $available_hooks = [
        'head',
        'toplinks',
        'tabs',
        'trail',
        'navlinks',
        'actionbuttons',
        'tree',
        'logout',
    ];
    private $actions = [];
    private $hooks   = [];

    /**
     * Register the plugins
     * @param $this->language - Language that have been used.
     */
    public function __construct(\Slim\Container $container)
    {

        $this->language = $container->has('language') ? $container->get('language') : 'english';
        $this->lang     = $container->get('lang');
        $this->conf     = $container->get('conf');

        if (!isset($this->conf['plugins'])) {
            return;
        }

        // Get the activated plugins
        $plugins = $this->conf['plugins'];

        foreach ($plugins as $activated_plugin) {
            $plugin_file = BASE_PATH . '/src/plugins/' . $activated_plugin . '/plugin.php';

            // Verify is the activated plugin exists
            if (file_exists($plugin_file)) {
                include_once $plugin_file;
                try {
                    $plugin = new $activated_plugin($this->language);
                    $this->add_plugin($plugin);
                } catch (Exception $e) {
                    continue;
                }
            } else {
                printf($this->lang['strpluginnotfound'] . "\t\n", $activated_plugin);
                exit;
            }
        }
    }

    /**
     * Add a plugin in the list of plugins to manage
     * @param $plugin - Instance from plugin
     */
    public function add_plugin($plugin)
    {

        //The $plugin_name is the identification of the plugin.
        //Example: PluginExample is the identification for PluginExample
        //It will be used to get a specific plugin from the plugins_list.
        $plugin_name                      = $plugin->get_name();
        $this->plugins_list[$plugin_name] = $plugin;

        //Register the plugin's functions
        $hooks = $plugin->get_hooks();
        foreach ($hooks as $hook => $functions) {
            if (!in_array($hook, $this->available_hooks)) {
                printf($this->lang['strhooknotfound'] . "\t\n", $hook);
                exit;
            }
            $this->hooks[$hook][$plugin_name] = $functions;
        }

        //Register the plugin's actions
        $actions                     = $plugin->get_actions();
        $this->actions[$plugin_name] = $actions;
    }

    public function getPlugin($plugin)
    {
        if (isset($this->plugins_list[$plugin])) {
            return $this->plugins_list[$plugin];
        }

        return null;
    }

    /**
     * Execute the plugins hook functions when needed.
     * @param $hook - The place where the function will be called
     * @param $function_args - An array reference with arguments to give to called function
     */
    public function do_hook($hook, &$function_args)
    {
        if (isset($this->hooks[$hook])) {
            foreach ($this->hooks[$hook] as $plugin_name => $functions) {
                $plugin = $this->plugins_list[$plugin_name];
                foreach ($functions as $function) {
                    if (method_exists($plugin, $function)) {
                        call_user_func([$plugin, $function], $function_args);
                    }
                }
            }
        }
    }

    /**
     * Execute a plugin's action
     * @param $plugin_name - The plugin name.
     * @param $action - action that will be executed.
     */
    public function do_action($plugin_name, $action)
    {

        if (!isset($this->plugins_list[$plugin_name])) {
            // Show an error and stop the application
            printf($this->lang['strpluginnotfound'] . "\t\n", $plugin_name);
            exit;
        }
        $plugin = $this->plugins_list[$plugin_name];

        // Check if the plugin's method exists and if this method is an declared action.
        if (method_exists($plugin, $action) and in_array($action, $this->actions[$plugin_name])) {
            call_user_func([$plugin, $action]);
        } else {
            // Show an error and stop the application
            printf($this->lang['stractionnotfound'] . "\t\n", $action, $plugin_name);
            exit;
        }
    }
}
