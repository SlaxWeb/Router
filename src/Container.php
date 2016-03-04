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
}
