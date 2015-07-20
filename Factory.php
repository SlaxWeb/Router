<?php
namespace SlaxWeb\Router;

use SlaxWeb\Router\Router;
use SlaxWeb\Router\Request;

class Factory
{
    public static function init()
    {
        $request = new Request;
        if (php_sapi_name() === "cli") {
            $options = getopt("u:", ["uri:"]);
            if (isset($options["u"])) {
                $options["uri"] = $options["u"];
            }
            $request->setUpCLI($options["uri"]);
        } else {
            $request->setUpRequest(
                isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null,
                isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : null,
                isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null,
                isset($_SERVER["SCRIPT_FILENAME"]) ? basename($_SERVER["SCRIPT_FILENAME"]) : null,
                isset($_SERVER["QUERY_STRING"]) ? $_SERVER["QUERY_STRING"] : null,
                (isset($_SERVER["HTTPS"]) && empty($_SERVR["HTTPS"]) === false && $_SERVER["HTTPS"] !== "off")
                    ? "https" : "http"
            );
        }

        $router = new Router($request);
        Helper::init($router, $request);

        return $router;
    }
}
