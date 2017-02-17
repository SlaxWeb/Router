<?php
namespace SlaxWeb\Router;

/**
 * Container class of Router component
 *
 * The Container class holds all Route definitions and provides access to said
 * Routes to the Processor class.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
class Container
{
    /**
     * Routes
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Current Route
     *
     * @var \SlaxWeb\Router\Route
     */
    protected $currentRoute = null;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger = null;

    /**
     * Default route
     *
     * @var \SlaxWeb\Router\Route
     */
    protected $defaultRoute = null;

    /**
     * 404 Route
     *
     * @var \SlaxWeb\Router\Route
     */
    protected $e404Route = null;

    /**
     * Class constructor
     *
     * Store the retrieved logger that implements the \Psr\Log\LoggerInterface
     * to the class protected property.
     *
     * @param \Psr\Log\LoggerInterface $logger Logger object
     */
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->logger->info("Route Container initialized");
    }

    /**
     * Add Route definition
     *
     * Add the retrieved Route to the internal Routes container array. If the
     * retrieved Route is not complete, throw the 'RouteIncompleteException'.
     *
     * @param \SlaxWeb\Router\Route $route Route definition object
     * @return self
     */
    public function add(Route $route): self
    {
        if ($route->uri === ""
            || $route->method === 0b0
            || $route->action === null) {
            $this->logger->error("Route incomplete. Unable to add");
            $this->logger->debug("Incomplete Route", [$route]);
            throw new Exception\RouteIncompleteException(
                "Retrieved Route is not complete and can not be stored"
            );
        }

        // store default route
        if ($route->isDefault) {
            if ($this->defaultRoute === null) {
                $this->defaultRoute = $route;
            } else {
                $this->logger->debug(
                    "Default route already added to container, skipping add.",
                    ["route" => $route, "storedDefault" => $this->defaultRoute]
                );
            }
        }

        // store 404 route
        if ($route->is404) {
            if ($this->e404Route === null) {
                $this->e404Route = $route;
                $this->logger->info("404 route added to Route Container");
            } else {
                $this->logger->debug(
                    "404 route already added to container, skipping add.",
                    ["route" => $route, "stored404" => $this->e404Route]
                );
            }

            return $this;
        }

        // store route to regular container
        $this->routes[] = $route;

        $this->logger->info(
            "Route successfully added to Container",
            ["uri" => $route->uri]
        );

        return $this;
    }

    /**
     * Get default route
     *
     * Returns the default route if set. If the default Route is not set, it returns
     * null.
     *
     * @return \SlaxWeb\Router\Route|null
     */
    public function defaultRoute()
    {
        return $this->defaultRoute;
    }

    /**
     * Get the 404 route
     *
     * Returns the 404 route if set. If the 404 Route is not set, it returns
     * null.
     *
     * @return \SlaxWeb\Router\Route|null
     */
    public function get404Route()
    {
        return $this->e404Route;
    }

    /**
     * Get all Routes
     *
     * Return all sotred routes as an array.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->routes;
    }

    /**
     * Get next Route
     *
     * Get the next Route, if the current Route is not yet set, return the first
     * Route. If no next element is found, false is returned.
     *
     * @return \SlaxWeb\Router\Route|bool
     */
    public function next()
    {
        $func = "next";
        if ($this->currentRoute === null) {
            $func = "current";
        }
        return $this->iterateRoutes($func);
    }

    /**
     * Get previous Route
     *
     * Get the previous Route, if the current Route is not yet set, return the
     * last Route. If no previous element is found, false is returned.
     *
     * @return \SlaxWeb\Router\Route|bool
     */
    public function prev()
    {
        $func = "prev";
        if ($this->currentRoute === null) {
            $func = "end";
        }
        return $this->iterateRoutes($func);
    }

    /**
     * Iterate internal Routes array
     *
     * Provides a unified method for iterating the Routes array with PHP
     * functions, 'next', 'prev', 'current', and 'end'. Returns the Route on the
     * position that is desired, if no Route is found, false is returned.
     *
     * @param string $function Function name for array iteration
     * @return \SlaxWeb\Router\Route|bool
     */
    protected function iterateRoutes(string $function)
    {
        if (($route = $function($this->routes)) !== false) {
            $this->currentRoute = $route;
            return $this->currentRoute;
        }
        return false;
    }
}
