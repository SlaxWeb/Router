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
    }
}
