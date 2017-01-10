<?php
namespace SlaxWeb\Router\Exception;

/**
 * Route Not Found Exception
 *
 * Thrown when now mathing Route definition was found for the Request, and no
 * 404 Route was defined.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
class RouteNotFoundException extends \Exception
{
}
