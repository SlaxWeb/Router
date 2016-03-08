<?php
/**
 * Factory
 *
 * Factory for the Router provides easier initialization for the Route,
 * Container, and Dispatcher classes of the Router component.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router;

class Factory
{
    /**
     * New Route
     *
     * Simply return a new instance of the Route class
     *
     * @return \SlaxWeb\Router\Route
     */
    public static function newRoute(): Route
    {
        return new Route;
    }
}
