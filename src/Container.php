<?php
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
 * @version   0.3
 */
namespace SlaxWeb\Router;

class Container
{
    /**
     * Routes
     *
     * @var array
     */
    protected $_routes = [];

    /**
     * Current Route
     *
     * @var \SlaxWeb\Router\Route
     */
    protected $_currentRoute = null;

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
            || $route->method === ""
            || $route->action === null) {
            throw new Exception\RouteIncompleteException(
                "Retrieved Route is not complete and can not be stored"
            );
        }

        $this->_routes[] = $route;

        return $this;
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
        return $this->_routes;
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
        if ($this->_currentRoute === null) {
            $func = "current";
        }
        return $this->_iterateRoutes($func);
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
        if ($this->_currentRoute === null) {
            $func = "end";
        }
        return $this->_iterateRoutes($func);
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
    protected function _iterateRoutes(string $function)
    {
        if (($route = $function($this->_routes)) !== false) {
            $this->_currentRoute = $route;
            return $this->_currentRoute;
        }
        return false;
    }
}
