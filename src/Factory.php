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

use SlaxWeb\Hooks\Factory as Hooks;
use SlaxWeb\Logger\Factory as Logger;
use SlaxWeb\Config\Container as Config;

class Factory
{
    /**
     * Routes Container
     *
     * @var \SlaxWeb\Router\Container
     */
    protected static $_container = null;

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

    /**
     * Initialize Routes Container
     *
     * Initializes the Routes Container. The Container requires the Logger, and
     * it in turn requires the Config component, so this initialization method
     * requires the Config component, even when the Container component does not
     * need it directly. Stores instance of Routes Container in Factories
     * protected property.
     *
     * @param \SlaxWeb\Config\Container $config Configuration container
     * @return Container
     */
    public static function container(Config $config): Container
    {
        if (self::$_container === null) {
            self::$_container = new Container(Logger::init($config));
        }
        return self::$_container;
    }

    /**
     * Initializes Route Dispatcher
     *
     * The Route Dispatcher requires the Routes Container, the Hooks Container,
     * as well as the Logger. As the input it only requires the Config Container
     * and it will instantiate all other components.
     *
     * @param \SlaxWeb\Config\Container $config Configuration container
     * @return Dispatcher
     */
    public static function dispatcher(Config $config): Dispatcher
    {
        return new Dispatcher(
            self::container($config),
            Hooks::init($config),
            Logger::init($config)
        );
    }

    /**
     * Get Request Object
     *
     * Creates a new Request object from superglobalsand returns it to the
     * caller.
     *
     * @return \SlaxWeb\Router\Request
     */
    public static function getRequest(): Request
    {
        return Request::createFromGlobals();
    }

    /**
     * Get Response Object
     *
     * Creates a new empty Response object and returns i to the caller.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function getResponse(): Response
    {
        return new Response;
    }
}
