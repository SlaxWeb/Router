<?php
/**
 * Request router Factory
 *
 * SlaxWeb\Router Factory instantiates the Request and Response components
 * and injects them into the Router.
 *
 * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @package \SlaxWeb\Router
 * @version v0.3
 * @license MIT
 *
 * @copyright (c) 2015 Tomaz Lovrec
 */
namespace SlaxWeb\Router;

use SlaxWeb\Router\Router as Router;
use SlaxWeb\Router\Request as Request;
use SlaxWeb\Router\Response as Response;

class RouterFactory
{
    public static function init(\Psr\Log\LoggerInterface $logger = null)
    {
        return static::_initRouter(
            static::_initRequest(),
            static::_initResponse($logger)
        );
    }

	protected static function _initRequest()
	{
        $request = new Request;
        if (php_sapi_name() === "cli") {
            $options = getopt("u:", ["uri:"]);
            if (isset($options["u"])) {
                $options["uri"] = $options["u"];
            }
            $request->setUpCLI($options["uri"]);
        } else {
            $request->setBaseRequest(
                (isset($_SERVER["HTTPS"]) && empty($_SERVER["HTTPS"]) === false && $_SERVER["HTTPS"] !== "off")
                    ? "https" : "http",
                isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null,
                isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : null
            );
            $request->parseRequestUri(
                isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null,
                isset($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : null
            );
        }

        return $request;
	}

	protected static function _initResponse(\Psr\Log\LoggerInterface $logger = null)
	{
        return new Response($logger);
	}

	protected static function _initRouter(Request $request, Response $response)
	{
        $router = new Router($request);
        Helper::init($router, $request);
	}
}
