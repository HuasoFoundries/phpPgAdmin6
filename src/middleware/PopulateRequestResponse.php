<?php

/**
 * PHPPgAdmin 6.0.0
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
        $container['requestobj'] = $request;
        $container['responseobj'] = $response;
        $route = $request->getAttribute('route');

        $container['server'] = $request->getParam('server');
        $container['database'] = $request->getParam('database');
        $container['schema'] = $request->getParam('schema');
        $misc = $container->get('misc');
        $view = $container->get('view');

        $misc->setHREF();
        $view->setForm();

        $view->offsetSet('METHOD', $request->getMethod());

        if ($route) {
            $view->offsetSet('subject', $route->getArgument('subject'));
            $container['server'] = $route->getArgument('server', $request->getParam('server'));
        }

        $query_string = $request->getUri()->getQuery();
        $view->offsetSet('query_string', $query_string);
        $path = ($subfolder ? ($subfolder . '/') : '')
        . $request->getUri()->getPath() . ($query_string ? '?' . $query_string : '');
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
            return ($container->haltHandler)($container->request, $container->response, $container['errors'], 412);
        }

        // First execute anything else
        $response = $next($request, $response);

        // Any other request, pass on current response
        return $response;
    }
}
