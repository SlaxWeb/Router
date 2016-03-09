<?php
/**
 * Router Service Provider
 *
 * Router Service Provider exposes classes of the Router component to the
 * dependency injection container.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router\Service;

use Pimple\Container;
use SlaxWeb\Router\Route;
use SlaxWeb\Router\Request;
use Symfony\Component\HttpFoundation\Response;
use SlaxWeb\Router\Container as RoutesContainer;
use SlaxWeb\Router\Dispatcher as RouteDispatcher;

class Provider implements \Pimple\ServiceProviderInterface
{
    /**
     * Register provider
     *
     * Register the Hooks Service Provider to the DIC.
     *
     * @param \Pimple\Container $container DIC
     * @return void
     */
    public function register(Container $container)
    {
        // new Route class instance
        $container["router.newRoute"] = $container->factory(function () {
            return new Route;
        });

        /*
         * Routes Container
         *
         * Requires the Logger Service Provider to be registered prior to its
         * own instantiation.
         */
        $container["routesContainer.service"] = function (Container $cont) {
            return new RoutesContainer($cont["logger.service"]);
        };

        /*
         * Route Dispatcher
         *
         * Requires the Routes Container, the Hooks Container, and the Logger.
         * This Service gathers all required services, and instantiates the
         * Dispatcher. Just make sure all required service providers are
         * registered prior to instantiating the Dispatcher
         */
        $container["routeDispatcher.service"] = function (Container $cont) {
            return new RouteDispatcher(
                $cont["routesContainer.service"],
                $cont["hooks.service"],
                $cont["logger.service"]
            );
        };

        // new Request object from superglobals
        $container["request.service"] = function () {
            return Request::createFromGlobals();
        };

        // new empty Response object
        $container["response.service"] = function () {
            return new Response;
        };
    }
}
