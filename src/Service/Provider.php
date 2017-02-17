<?php
namespace SlaxWeb\Router\Service;

use Pimple\Container;
use SlaxWeb\Router\Route;
use SlaxWeb\Router\Request;
use SlaxWeb\Router\Response;
use SlaxWeb\Router\Container as RoutesContainer;
use SlaxWeb\Router\Dispatcher as RouteDispatcher;
use Symfony\Component\HttpFoundation\ParameterBag;

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
 * @version   0.4
 */
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
            return new RoutesContainer($cont["logger.service"]("System"));
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
            $dispatcher = new RouteDispatcher(
                $cont["routesContainer.service"],
                $cont["hooks.service"],
                $cont["logger.service"]("System")
            );

            $config = $cont["config.service"];
            if ($config["app.segmentBasedMatch"] === true) {
                $dispatcher->enableSegMatch(
                    $config["app.controllerNamespace"],
                    [$cont],
                    $config["app.segmentBasedUriPrepend"],
                    $config["app.segmentBasedDefaultMethod"]
                );
            }
            return $dispatcher;
        };

        // new Request object from superglobals or pre set base url
        $container["request.service"] = function (Container $cont) {
            if (isset($cont["requestParams"])) {
                $method = $cont["requestParams"]["method"] ?? $_SERVER["REQUEST_METHOD"];
                $request = Request::create(
                    $cont["requestParams"]["uri"],
                    $method,
                    array_merge($_GET, $_POST),
                    $_COOKIE,
                    $_FILES,
                    $_SERVER
                );

                /*
                 * prepare request parameters from request content, copy from
                 * Symfony Http Foundation Request method "createFromGlobals"
                 */
                if (strpos($request->headers->get("CONTENT_TYPE"), "application/x-www-form-urlencoded") === 0
                    && in_array(strtoupper($request->server->get("REQUEST_METHOD", "GET")),
                        ["PUT", "DELETE", "PATCH"])) {
                    parse_str($request->getContent(), $data);
                    $request->request = new ParameterBag($data);
                }
            } else {
                $request = Request::createFromGlobals();
            }

            return $request;
        };

        // new empty Response object
        $container["response.service"] = function () {
            return new Response;
        };

        $this->setAppProperties($container);
    }

    /**
     * Set application properties
     *
     * Sets the Router related data to application properties.
     *
     * @param \Pimple\Container $container DIC
     * @return void
     */
    protected function setAppProperties(Container $container)
    {
        $container["basePath"] = $container["request.service"]->getBasePath();
        $container["baseUrl"] = $container["request.service"]
            ->getSchemeAndHttpHost() . $container["basePath"];
    }
}
