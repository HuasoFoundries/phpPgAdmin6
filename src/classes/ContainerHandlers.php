<?php

/**
 * PHPPgAdmin 6.1.0
 */

namespace PHPPgAdmin;

/**
 * Auxiliary class to handle injection of dependencies to avoid
 * declaring them in the container class.
 */
class ContainerHandlers
{
    /**
     * @var \PHPPgAdmin\ContainerUtils
     * */
    private $container;

    /**
     * @param \PHPPgAdmin\ContainerUtils $container
     */
    public function __construct(\PHPPgAdmin\ContainerUtils $container)
    {
        $this->container = $container;
    }

    public function storeMainRequestParams(): self
    {
        $this->container['action'] = $_REQUEST['action'] ?? '';
        // This should be deprecated once we're sure no php scripts are required directly
        $this->container->offsetSet('server', $_REQUEST['server'] ?? null);
        $this->container->offsetSet('database', $_REQUEST['database'] ?? null);
        $this->container->offsetSet('schema', $_REQUEST['schema'] ?? null);

        return $this;
    }

    /**
     * Sets the views.
     *
     * @return self ( description_of_the_return_value )
     */
    public function setViews(): self
    {
        $container = $this->container;

        /**
         * @return \PHPPgAdmin\ViewManager
         */
        $container['view'] = static function (\PHPPgAdmin\ContainerUtils $c): \PHPPgAdmin\ViewManager {
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
    public function setMisc(): self
    {
        $container = $this->container;
        /**
         * @return \PHPPgAdmin\Misc
         */
        $container['misc'] = static function (\PHPPgAdmin\ContainerUtils $c): \PHPPgAdmin\Misc {
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

    public function setExtra(): self
    {
        $container = $this->container;
        $container['flash'] = static function (): \Slim\Flash\Messages {
            return new \Slim\Flash\Messages();
        };

        $container['lang'] = static function (\PHPPgAdmin\ContainerUtils $c): array {
            $translations = new \PHPPgAdmin\Translations($c);

            return $translations->lang;
        };

        return $this;
    }

    public function setHaltHandler(): self
    {
        $this->container['haltHandler'] = static function (\PHPPgAdmin\ContainerUtils $c) {
            return static function ($request, $response, $exits, $status = 500) {
                $title = 'PHPPgAdmin Error';

                $html = '<p>The application could not run because of the following error:</p>';

                $output = \sprintf(
                    "<html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>" .
                        '<title>%s</title><style>' .
                        'body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}' .
                        'h3{margin:0;font-size:28px;font-weight:normal;line-height:30px;}' .
                        'span{display:inline-block;font-size:16px;}' .
                        '</style></head><body><h3>%s</h3><p>%s</p><span>%s</span></body></html>',
                    $title,
                    $title,
                    $html,
                    \implode('<br>', $exits)
                );

                $body = $response->getBody(); //new \Slim\Http\Body(fopen('php://temp', 'r+'));
                $body->write($output);

                return $response
                    ->withStatus($status)
                    ->withHeader('Content-type', 'text/html')
                    ->withBody($body);
            };
        };

        return $this;
    }
}
