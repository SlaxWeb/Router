<?php
namespace SlaxWeb\Router;

use SlaxWeb\Router\Router;
use SlaxWeb\Router\Request;

class Factory
{
    public static function init()
    {
        $request = new Request;
        $router = new Router($request);
        Helper::init($router, $request);

        return $router;
    }
}
