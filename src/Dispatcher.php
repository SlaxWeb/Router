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
use SlaxWeb\Router\Response;

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
     * 404 Route
     *
     * @var \SlaxWeb\Router\Route
     */
    protected $_404Route = null;

    /**
     * Additional query params
     *
     * @var array
     */
    protected $_addQueryParams = [];

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
     * @param \SlaxWeb\Router\Reponse $response Response object
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

        $route = $this->_findRoute($requestMethod, $requestUri);
        if ($route === null) {
            $response->setStatusCode(404);
            if ($this->_404Route !== null) {
                $route = $this->_404Route;
            }
        }
        if ($route !== null) {
            // add query parameters if defined
            if (empty($this->_addQueryParams) === false) {
                $request->addQuery($this->_addQueryParams);
            }

            $params = array_merge(
                [$request, $response],
                array_slice(func_get_args(), 2)
            );

            $result = $this->_hooks->exec(
                "router.dispatcher.beforeDispatch",
                $route
            );
            // check hook results permit route execution
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
                . " HTTP Method '{$requestMethod}'"
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
        while (($route = $this->_routes->next()) !== false) {
            if ($route->uri === "404RouteNotFound") {
                $this->_404Route = $route;
                continue;
            }

            if ($method !== $route->method) {
                continue;
            }

            $uriMatch = preg_match_all(
                $this->_definedPosix2Pcre($route->uri),
                $uri,
                $matches
            );
            if ($uriMatch === 0) {
                continue;
            }

            $this->_hooks->exec("router.dispatcher.routeFound", $route);
            $this->_logger->info("Route match found");

            if (is_array($matches)) {
                $this->_addParams($matches);
            }

            return $route;
        }

        $this->_hooks->exec("router.dispatcher.routeNotFound");
        return null;
    }

    /**
     * POSIX named class to PCRE capturing group
     *
     * Replace the special POSIX named classes with normal named capturing
     * groups.
     *
     * @param string $regex Raw regexp string
     * @param array $names POSIX class names array, default: ["params", "named"]
     * @return string Replaced regexp string
     */
    protected function _definedPosix2Pcre(
        string $regex,
        array $names = ["params", "named"]
    ): string {
        $counters = [];
        foreach ($names as $type) {
            $regex = preg_replace_callback(
                "~\[:{$type}:\]~",
                function (array $matches) use (&$counters, $type) {
                    if (isset($counters[$type]) === false) {
                        $counters[$type] = 0;
                    }
                    $counters[$type]++;
                    $changed = "(?P<{$type}{$counters[$type]}>.+?)";
                    return $changed;
                },
                $regex
            );
        }

        return $regex;
    }

    /**
     * Add additional parameters
     *
     * Prepares the found matches from the URI and injects them into the
     * '_addQueryParams' property.
     *
     * @param array $matches Regex matches
     * @return void
     */
    protected function _addParams(array $matches)
    {
        $params = [];
        foreach ($matches as $key => $value) {
            $value = $value[0];
            if (strpos($key, "params") === 0) {
                $params["parameters"] = array_merge(
                    $params["parameters"] ?? [],
                    explode("/", $value)
                );
            }

            if (strpos($key, "named") === 0) {
                $named = [];
                $key = "";
                foreach (explode("/", $value) as $param) {
                    if ($key === "") {
                        $key = $param;
                        $named[$key] = "";
                    } else {
                        $named[$key] = $param;
                        $key = "";
                    }
                }
                $params = array_merge($params, $named);
            }
        }

        $this->_addQueryParams = $params;
    }
}
