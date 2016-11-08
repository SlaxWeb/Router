<?php
namespace SlaxWeb\Router;

use SlaxWeb\Router\Request;
use SlaxWeb\Router\Response;

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
class Dispatcher
{
    /**
     * Routes Container
     *
     * @var \SlaxWeb\Router\Container
     */
    protected $routes = null;

    /**
     * Hooks Container
     *
     * @var \SlaxWeb\Hooks\Container
     */
    protected $hooks = null;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * Additional query params
     *
     * @var array
     */
    protected $addQueryParams = [];

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
        $this->routes = $routes;
        $this->hooks = $hooks;
        $this->logger = $logger;

        $this->logger->info("Router Dispatcher initialized");

        $this->hooks->exec("router.dispatcher.afterInit");
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
        $method = $request->getMethod();
        $requestMethod = constant("\\SlaxWeb\\Router\\Route::METHOD_"
            . $method);
        $requestUri = ltrim($request->getPathInfo(), "/");

        $this->logger->info(
            "Trying to find match for ({$method}) '{$requestUri}'"
        );

        $route = $this->findRoute($requestMethod, $requestUri);
        // add query parameters if defined
        if (empty($this->addQueryParams) === false) {
            $request->addQuery($this->addQueryParams);
        }

        $result = $this->hooks->exec(
            "router.dispatcher.beforeDispatch",
            $route
        );
        // check hook results permit route execution
        if (($result === false
            || (is_array($result) && in_array(false, $result))) === false) {
            $this->logger->info(
                "Executing route definition",
                ["name" => $route->uri, "action" => $route->action]
            );
            ($route->action)(...func_get_args());
        }
        $this->hooks->exec("router.dispatcher.afterDispatch");
    }

    /**
     * Find matching Route
     *
     * Find the matching Route based on the Request Method and Request URI. If
     * no matching route is found, action from the 404 route is returned, if found.
     * If also the 404 Route is not found, the 'RouteNotFoundException' is thrown.
     * Otherwise the matching Route objects action Callable is returned.
     *
     * @param int $method Request Method
     * @param string $uri Request Uri
     * @return \SlaxWeb\Router\Route
     *
     * @exceptions \SlaxWeb\Router\Exception\RouteNotFoundException
     */
    protected function findRoute(int $method, string $uri): Route
    {
        $notFoundRoute = null;
        while (($route = $this->routes->next()) !== false) {
            if ($route->uri === "404RouteNotFound") {
                $notFoundRoute = $route;
                continue;
            }

            if (($route->method & $method) !== $method) {
                continue;
            }

            // Default URI, check if default route?
            if ($uri === "" && $route->isDefault) {
                return $route;
            }

            $uriMatch = preg_match_all(
                $this->posix2Pcre($route->uri),
                $uri,
                $matches
            );
            if ($uriMatch === 0) {
                continue;
            }

            $this->logger->info("Route match found");
            if (is_array($matches)) {
                $this->addParams($matches);
            }

            return $route;
        }

        $result = $this->hooks->exec("router.dispatcher.routeNotFound");
        // check if hook call produced a valid Route object
        if ($result instanceof Route) {
            $this->logger->info("No Route found, hook call produced valid Route object, using it instead.");
            return $result;
        } elseif (is_array($result)) {
            foreach ($result as $r) {
                if ($r instanceof Route) {
                    $this->logger->info("No Route found, hook call produced valid Route object, using it instead.");
                    return $r;
                }
            }
        }

        if ($notFoundRoute !== null) {
            return $notFoundRoute;
        }
        $this->logger->error("No Route found, and no 404 Route defined");
        throw new Exception\RouteNotFoundException(
            "No Route definition found for Request URI '{$uri}' with"
            . " HTTP Method '{$method}'"
        );
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
    protected function posix2Pcre(
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
     * 'addQueryParams' property.
     *
     * @param array $matches Regex matches
     * @return void
     */
    protected function addParams(array $matches)
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

        $this->addQueryParams = $params;
    }
}
