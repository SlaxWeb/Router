<?php
namespace \SlaxWeb\Router;

use SlaxWeb\Router\Router;
use SlaxWeb\Router\Request;

class Factory
{
    public function init()
    {
        $request = new Request;
        $router = new Router($request);

        return $router;
    }
}
