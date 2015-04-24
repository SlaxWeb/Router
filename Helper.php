<?php
namespace SlaxWeb\Router;

use SlaxWeb\Router\Router;
use SlaxWeb\Router\Request;

class Helper
{
    protected static $_router = null;
    protected static $_request = null;

    public static function init(Router $router, Request $request)
    {
        self::$_router = $router;
        self::$_request = $request;
    }

    public static function getUrl($uri)
    {
        $url = self::$_request->url . "/{$uri}";
    }
}
