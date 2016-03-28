# Router

[![Build Status](https://travis-ci.org/SlaxWeb/Router.svg?branch=0.3.0)](https://travis-ci.org/SlaxWeb/Router)

The Router Component is one of the core components of the SlaxWeb/Framework, but
it can be used separately as well. It relies on the following components:
* [Logger](https://github.com/SlaxWeb/Logger)
* [Hooks](https://github.com/SlaxWeb/Hooks)
* [Config](https://github.com/SlaxWeb/Config)
* [Symfony HTTP Foundation](https://github.com/symfony/http-foundation)

The Router helps you route your incoming requests to the correct code, and is
one of the essential components in modern web applications, especially those who
rely on a single entry point. With a Router, you control where your incoming
requests are handled.

## Installation

Installation is currently available only through composer, and can be achieved
by putting the following into your *composer.json* file:
```json
"require": {
    "slaxweb/router": "0.4.*@dev"
},
"minimum-stability": "dev"
```

## Usage

Initialization of the Router can be a little bit overwhelming, especially
because it relies on two other components, which also need to be initialized
separately. Because of this, the Router provides two convenient ways of
initializing it, a Factory and a Service Provider if you happen to use the
Pimple Dependency Injection Container.

This is just to get you started. Full documentation will follow in the future.

### Factory

The Factory provides static methods to initialize the Router properly, and help
you get your Route definitions to the Router. This example does not show how to
prepare the Config component, which is required by the Logger component. For
this please refer to the Config and Logger components.
```php
<?php
use SlaxWeb\Router\Route;
use SlaxWeb\Router\Factory;
use SlaxWeb\Router\Request;
use SlaxWeb\Config\Factory as Config;
use Symfony\Component\HttpFoundation\Response;

require_once "vendor/autoload.php";

$config = Config::init();
// load config, refer to Config component README

// define a route
$route = Factory::newRoute()->set(
    "myUrl",
    Route::METHOD_GET,
    function (Request $request, Response $response) {
        // ...
        $response->setContent("my content");
    }
);
Factory::container($config)->add($route);

// dispatch the request
$response = Factory::response();
Factory::dispatcher($config)->dispatch(Factory::request(), $response);

// and send response to browser
$response->send()
```

Now if you visit *http://yourdomain.com/script.php/myUrl* in your browser, you
should see '**my content**' in your browser.

### Service Provider

Using the service provider is quite similar, and can make it a bit easier for
you.
```php
<?php
require_once "vendor/autoload.php";

// init the Pimple Container
$container = new Pimple\Container;

// register services
$container->register(new SlaxWeb\Config\Service\Provider);
$container->register(new SlaxWeb\Logger\Service\Provider);
$container->register(new SlaxWeb\Hooks\Service\Provider);
$container->register(new SlaxWeb\Router\Service\Provider);

// load config, refer to Config component README

// defina a route
$route = $container["router.newRoute"]->set(
    "myUrl",
    Route::METHOD_GET,
    function (Request $request, Response $response) {
        // ...
        $response->setContent("my content");
    }
);
$container["routesContainer.service"]->add($route);

// dispatch the request
$container["routeDispatcher.service"]->dispatch(
    $container["request.service"],
    $container["response.service"]
);

// and send response to browser
$container["response.service"]->send();
```

And if you visit *http://yourdomain.com/script.php/myUrl* again, you should
again see '**my content**' in your browser.
