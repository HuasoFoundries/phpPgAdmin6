<?php

/**
 * PHPPgAdmin v6.0.0-RC9-3-gd93ec300
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
        \Psr\Http\Message\ServerRequestInterface $request,
        \Psr\Http\Message\ResponseInterface $response,
        $next
    ) {
        $container = $this->container;
        $container['requestobj'] = $request;
        $container['responseobj'] = $response;

        $container['server'] = $request->getParam('server');
        $container['database'] = $request->getParam('database');
        $container['schema'] = $request->getParam('schema');
        $misc = $container->get('misc');

        $misc->setHREF();
        $misc->setForm();

        $container->view->offsetSet('METHOD', $request->getMethod());

        if ($request->getAttribute('route')) {
            $container->view->offsetSet('subject', $request->getAttribute('route')->getArgument('subject'));
        }

        $query_string = $request->getUri()->getQuery();
        $container->view->offsetSet('query_string', $query_string);
        $path = (SUBFOLDER ? (SUBFOLDER . '/') : '') . $request->getUri()->getPath() . ($query_string ? '?' . $query_string : '');
        $container->view->offsetSet('path', $path);

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
        $container->view->offsetSet('params', $viewparams);
        $container->view->offsetSet('in_test', $in_test);

        if (0 < \count($container['errors'])) {
            return ($container->haltHandler)($container->requestobj, $container->responseobj, $container['errors'], 412);
        }

        $messages = $container->flash->getMessages();
        /*if (!empty($messages)) {
        foreach ($messages as $key => $message) {
        $this->prtrace('Flash: ' . $key . ' =  ' . json_encode($message));
        }
        }*/

        // First execute anything else
        $response = $next($request, $response);

        // Any other request, pass on current response
        return $response;
    }
}
