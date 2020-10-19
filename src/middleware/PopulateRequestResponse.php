<?php

/**
 * PHPPgAdmin 6.1.3
 */

namespace PHPPgAdmin\Middleware;

/**
 * Set the requestobj and responseobj properties of the container
 * as the value of $request and $response, which already contain the route.
 */
class PopulateRequestResponse extends Middleware
{
    use \PHPPgAdmin\Traits\HelperTrait;

    public function __invoke(
        \Slim\Http\Request $request,
        \Slim\Http\Response $response,
        $next
    ) {
        $container = $this->container;
        $subfolder = $this->container->getSubfolder();

        $route = $request->getAttribute('route');

        $container['server'] = $request->getParam('server');
        $container['database'] = $request->getParam('database');
        $container['schema'] = $request->getParam('schema');
        $misc = $container->get('misc');

        $view = $this->getViewManager($container);

        $misc->setHREF();
        $view->setForm();

        $view->offsetSet('METHOD', $request->getMethod());

        if ($route) {
            $view->offsetSet('subject', $route->getArgument('subject'));
            $container['server'] = $route->getArgument('server', $request->getParam('server'));
        }

        $request = $request->withUri($this->getUri($request)->withBasePath($subfolder));
        $uri = $request->getUri();
        $query_string = $uri->getQuery();
        $requestPath = $uri->getPath();

        $view->offsetSet('query_string', $query_string);
        $path = $requestPath . ($query_string ? '?' . $query_string : '');
        $view->offsetSet('path', $path);

        $params = $request->getParams();

        $viewparams = [];

        foreach ($params as $key => $value) {
            if (\is_scalar($value)) {
                $viewparams[$key] = $value;
            }
        }

        if (isset($_COOKIE['IN_TEST'])) {
            $in_test = (string) $_COOKIE['IN_TEST'];
        } else {
            $in_test = '0';
        }

        // remove tabs and linebreaks from query
        if (isset($params['query'])) {
            $viewparams['query'] = \str_replace(["\r", "\n", "\t"], ' ', $params['query']);
        }
        $view->offsetSet('params', $viewparams);
        $view->offsetSet('in_test', $in_test);

        if (0 < \count($container['errors'])) {
            return ($container->haltHandler)($request, $response, $container['errors'], 412);
        }
        $enqueued_reload_browser = ($container->flash->getFirstMessage('reload_browser') ?? false);

        if ($enqueued_reload_browser) {
            $view->setReloadBrowser($enqueued_reload_browser);
        }
        // First execute anything else
        $response = $next($request, $response);

        // Any other request, pass on current response
        return $response;
    }

    private function getUri(\Slim\Http\Request $request): \Slim\Http\Uri
    {
        return $request->getUri();
    }

    private function getViewManager(\PHPPgAdmin\ContainerUtils $container): \PHPPgAdmin\ViewManager
    {
        return $container->get('view');
    }
}
