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
 * @version   0.4
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
     * Segment Based URI Matching
     *
     * On array of settings for Segment Based URI matching. Cotnains the following
     * keys:
     * enabled - false - Is Segment Based URI matching enabled
     * uriPrepend - "" - URI prepend, only URIs prepended with this prepend are
     *                   used for Segment Based URI matching
     * controller: - controller settings
     *    namespace - "" - Controller namespace
     *    defaultMethod - "" - Default method for the controller if the segment
     *                         for the controller method is not found in the URI
     *    params - [] - Controller constructor parameters
     *
     * @var array
     */
    protected $segBasedMatch = [
        "enabled"       =>  false,
        "uriPrepend"    =>  "",
        "controller"    =>  [
            "namespace"     =>  "",
            "defaultMethod" =>  "",
            "params"        =>  []
        ]
    ];

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
     *
     * @exceptions \SlaxWeb\Router\Exception\RouteNotFoundException
     */
    public function dispatch(Request $request, Response $response)
    {
        $method = $request->getMethod();
        $requestMethod = constant("\\SlaxWeb\\Router\\Route::METHOD_"
            . $method);
        $requestUri = trim($request->getPathInfo(), "/");

        $this->logger->info(
            "Trying to find match for ({$method}) '{$requestUri}'"
        );

        if (($route = $this->findRoute($requestMethod, $requestUri)) === null) {
            // no route could be found, time to bail out
            $this->logger->error("No Route found, and no 404 Route defined");
            throw new Exception\RouteNotFoundException(
                "No Route definition found for Request URI '{$requestUri}' with"
                . " HTTP Method '{$method}'"
            );
        }

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
     * Enable segment Based URI Matching
     *
     * Enables the segment based URI matching, sets the Controller namespace, and
     * the default method to call if the second segment is not found in the URI.
     * Default method has the default value of string("index").
     *
     * @param string $namespace Controller namespace
     * @param array $params Controller constructor parameters
     * @param string $prepend URI prepend for segment based URI matching
     * @param string $defaultMethod Default controller method for single segment URIs
     * @return \SlaxWeb\Router\Dispatcher
     */
    public function enableSegMatch(
        string $namespace,
        array $params = [],
        string $prepend = "",
        string $defaultMethod = "index"
    ): Dispatcher {
        $this->segBasedMatch = [
            "enabled"       =>  true,
            "uriPrepend"    =>  $prepend,
            "controller"    =>  [
                "namespace"     =>  $namespace,
                "defaultMethod" =>  $defaultMethod,
                "params"        =>  $params
            ]
        ];
        return $this;
    }

    /**
     * Find matching Route
     *
     * Try and obtain the route that bests fits the request and return it. If no
     * such route is found, and no 404 route exists, nor is one returned from a
     * 'routeNotFound' hook execution, null is returned.
     *
     * @param int $method Request Method
     * @param string $uri Request Uri
     * @return \SlaxWeb\Router\Route|null
     */
    protected function findRoute(int $method, string $uri)
    {
        $route = null;
        // if URI is empty and a default route is set, use it instead
        if ($uri !== ""
            || ($route = $this->routes->defaultRoute()) === null
        ) {
            $route = $this->checkContainer($method, $uri);
        }

        // if route is still null execute the 'routeNotFound' hook, and if no route
        // is found, try and obtain the 404 route from the container
        if ($route === null
            && ($route = $this->routeNotFoundHook()) === null
        ) {
            $route = $this->routes->get404Route();
        }
        return $route;
    }

    /**
     * Check Routes Container
     *
     * Iterates the routes container and tries to match the request to a Route in
     * the container. Returns the matching Route object if found, or null otherwise.
     *
     * @param int $method Request Method
     * @param string $uri Request Uri
     * @return \SlaxWeb\Router\Route|null
     */
    protected function checkContainer(int $method, string $uri)
    {
        while (($route = $this->routes->next()) !== false) {
            if (($route->method & $method) !== $method) {
                continue;
            }

            if (preg_match_all($this->posix2Pcre($route->uri), $uri, $matches) === 0) {
                continue;
            }

            $this->logger->info("Route match found");
            if (is_array($matches)) {
                $this->addParams($matches);
            }

            return $route;
        }
        return null;
    }

    /**
     * Execute Route Not Found Hook
     *
     * Execute the Route Not Found Hook definition with the help of the Hook component
     * and return a valid Route object if it is found in the Hook execution return
     * data.
     *
     * @return \SlaxWeb\Router\Route|null
     */
    public function routeNotFoundHook()
    {
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
