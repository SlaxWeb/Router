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

class Provider extends \Pimple\ServiceProviderInterface
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
        $container["router.newRoute"] = $this->factory(function () {
            return new Route;
        });
    }
}
