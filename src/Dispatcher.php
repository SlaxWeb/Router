<?php
/**
 * Dispatcher
 *
 * Dispatcher is the main class of the Router component, it must find the
 * corresponding Route to the retrieved Request, and execute that Routes
 * callable definition and return the Response object.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router;

use SlaxWeb\Router\Request;
use Symfony\Component\HttpFoundation\Response;

class Dispatcher
{
    /**
     * Routes Container
     *
     * @var \SlaxWeb\Router\Container
     */
    protected $_routes = null;

    /**
     * Hooks Container
     *
     * @var \SlaxWeb\Hooks\Container
     */
    protected $_hooks = null;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger = null;

    /**
     * Class constructor
     *
     * Set retrieved Routes Container, Hooks Container, and the Logger to the
     * internal properties.
     *
     * @param \SlaxWeb\Router\Container $routes Routes container
     * @param \SlaxWeb\Hooks\Container $hooks Hooks container
     * @param \Psr\Log\LoggerInterface $logger Logger implementing PSR3
     */
    public function __construct(
        \SlaxWeb\Router\Container $routes,
        \SlaxWeb\Hooks\Container $hooks,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_routes = $routes;
        $this->_hooks = $hooks;
        $this->_logger = $logger;

        $this->_logger->info("Router Dispatcher initialized");

        $this->_hooks->exec("router.dispatcher.afterInit");
    }

    /**
     * Dispatch Request
     *
     * Dispatch the Request to the propper Route. Tries to find a matching Route
     * for the retrieved Request object, and calls that Routes action callable
     * along with Response, and any other input parameters as arguments for the
     * action.
     *
     * @param \SlaxWeb\Router\Request $request Request object
     * @param \Symfony\Component\HttpFoundation\Response $response Response
     *                                                             object
     * @param mixed $unknown Any further parameter is sent to Route action
     * @return void
     */
    public function dispatch(Request $request, Response $response)
    {
        $requestMethod = $request->getMethod();
        $requestUri = ltrim($request->getPathInfo(), "/");

        $this->_logger->info(
            "Trying to find match for ({$requestMethod}) '{$requestUri}'"
        );

        $params = array_merge(
            [$request, $response],
            array_slice(func_get_args(), 2)
        );

        $route = $this->_findRoute($requestMethod, $requestUri);
        if ($route !== null) {
            $result = $this->_hooks->exec(
                "router.dispatcher.beforeDispatch",
                $route
            );
            if (($result === false
                || (is_array($result) && in_array(false, $result))) === false) {
                $this->_logger->info(
                    "Executing route definition",
                    ["name" => $route->uri, "action" => $route->action]
                );
                ($route->action)(...$params);
            }
            $this->_hooks->exec("router.dispatcher.afterDispatch");
        } else {
            $this->_logger->error("No Route found, and no 404 Route defined");
            throw new Exception\RouteNotFoundException(
                "No Route definition found for Request URI '{$requestUri}' with"
                . "HTTP Method '{$requestMethod}'"
            );
        }
    }

    /**
     * Find matching Route
     *
     * Find the matching Route based on the Request Method and Request URI. If
     * no matching route is found, the 404 route is returned, if found. If also
     * the 404 Route is not found, then null is returned. Otherwise the matching
     * Route object is returned.
     *
     * @param string $method Request Method
     * @param string $uri Request Uri
     * @return \SlaxWeb\Router\Route|null
     */
    protected function _findRoute(string $method, string $uri)
    {
        $notFoundRoute = null;

        while (($route = $this->_routes->next()) !== false) {
            if ($route->uri === "404RouteNotFound") {
                $notFoundRoute = $route;
                continue;
            }

            if ($method !== $route->method) {
                continue;
            }

            $uriMatch = preg_match($route->uri, $uri);
            if ($uriMatch === 0) {
                continue;
            } elseif ($uriMatch === false) {
                // throw error
            }

            $this->_hooks->exec("router.dispatcher.routeFound", $route);
            $this->_logger->info("Route match found");
            return $route;
        }

        $this->_hooks->exec("router.dispatcher.routeNotFound");
        return $notFoundRoute;
    }
}
