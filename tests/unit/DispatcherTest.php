<?php
/**
 * Dispatcher Tests
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
namespace SlaxWeb\Router\Tests\Unit;

use SlaxWeb\Router\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Routes Container
     *
     * @var \SlaxWeb\Router\Container
     */
    protected $_container = null;

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
     * Prepare the test
     *
     * Instantiate the Routes Container, the Hooks Container, and the Logger
     * mock objects.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->_container = $this->getMockBuilder(
            "\\SlaxWeb\\Router\\Container"
        )->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->_hooks = $this->getMockBuilder("\\SlaxWeb\\Hooks\\Container")
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->_logger = $this->createMock("\\Psr\\Log\\LoggerInterface");
    }

    protected function tearDown()
    {
    }

    /**
     * Test constructor
     *
     * Ensure that the constructor takes the Routes Container, the Hooks
     * Container, and the Logger as input parameters, and that it logs about
     * Dispatcher initialization in the INFO log level.
     *
     * @return void
     */
    public function testConstructor()
    {
        $this->_logger->expects($this->once())
            ->method("info");

        new Dispatcher($this->_container, $this->_hooks, $this->_logger);
    }

    /**
     * Test Route Execution
     *
     * Test that the Dispatcher finds the correct Route based on the Request
     * object, and properly executes the Route callable.
     *
     * @return void
     */
    public function testRouteExecution()
    {
        // prepare container
        $routes = $this->prepareRoutes();
        $this->_container->expects($this->exactly(3))
            ->method("next")
            ->will(
                $this->onConsecutiveCalls($routes[0], $routes[1], $routes[2])
            );

        $this->_logger->expects($this->exactly(4))
            ->method("info");

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->once())
            ->method("getMethod")
            ->willReturn("PUT");

        $request->expects($this->once())
            ->method("getPathInfo")
            ->willReturn("/uri3");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with(\SlaxWeb\Router\Route::METHOD_PUT, 2);

        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Test dispatcher hooks
     *
     * Test that the dispatcher calls all the hooks it needs to, and that the
     * dispatcher stops further execution of the Route if the 'bedoreDispatch'
     * returns bool(false) or an array containing a bool(false) value.
     *
     * @return void
     */
    public function testDispatcherHooks()
    {
        // prepare container
        $routes = $this->prepareRoutes(1);
        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn($routes[0]);

        // prepare hooks
        $this->_hooks->expects($this->exactly(8))
            ->method("exec")
            ->withConsecutive(
                // normal execution
                ["router.dispatcher.beforeDispatch", $routes[0]],
                ["router.dispatcher.afterDispatch"],
                // stop by returning bool(false)
                ["router.dispatcher.beforeDispatch", $routes[0]],
                ["router.dispatcher.afterDispatch"],
                // stop by returning [bool(false)]
                ["router.dispatcher.beforeDispatch", $routes[0]],
                ["router.dispatcher.afterDispatch"],
                // route specific hook execution and stop by returning [bool(false)]
                ["routeBeforeDispatch", $routes[0]],
                ["routeAfterDispatch"]
            )->will(
                $this->onConsecutiveCalls(
                    // normal execution
                    "some return value",
                    null,
                    // stop by returning bool(false)
                    false,
                    null,
                    // stop by returning [bool(false)]
                    [false],
                    null,
                    // stop by returning [bool(false)]
                    [false],
                    null
                )
            );

        $this->_logger->expects($this->any())
            ->method("info");

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/uri1");

        $response = $this->createMock(
            "\\Slaxweb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with(\SlaxWeb\Router\Route::METHOD_GET, 0);

        // normal execution
        $dispatcher->dispatch($request, $response, $tester);
        // stopped execution through hooks return value bool(false)
        $dispatcher->dispatch($request, $response, $tester);
        // stopped execution through hooks return value [bool(false)]
        $dispatcher->dispatch($request, $response, $tester);
        // route specific hook execution and stop by returning [bool(false)]
        $routes[0]->setHook("routeBeforeDispatch");
        $routes[0]->setHook("routeAfterDispatch", true);
        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Test 404 Route
     *
     * Ensure that the 404 Route is invoked when no matching Route definition is
     * found, and that correct logging and hook calling occurs. Ensure when no
     * 404 route is found, that the 'RouteNotFoundException' is thrown.
     *
     * @return void
     */
    public function testRouteNotFound()
    {
        // prepare container
        $routes = $this->prepareRoutes(1);

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/noroute");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with(404);

        $routeMock = $this->getMockBuilder("\\SlaxWeb\\Router\\Route")
            ->setMethods(null)
            ->getMock();
        $routeMock->set404Route(
            function (
                \SlaxWeb\Router\Request $request,
                \SlaxWeb\Router\Response $response,
                $tester
            ) {
                $tester->call(404);
            }
        );
        $routes[] = $routeMock;

        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn($routes[0], false);

        $this->_container->expects($this->any())
            ->method("get404Route")
            ->willReturn($routes[1]);

        // prepare hooks
        $this->_hooks->expects($this->exactly(3))
            ->method("exec")
            ->withConsecutive(
                ["router.dispatcher.routeNotFound"],
                ["router.dispatcher.beforeDispatch", $routes[1]],
                ["router.dispatcher.afterDispatch"]
            );

        $this->_logger->expects($this->exactly(3))
            ->method("info");

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );
        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Test No 404 Route Exception
     *
     * When no Route matches the Request, and there is no 'No Route' Route set,
     * the Dispatcher must raise an 'RouteNotFoundException'
     *
     * @return void
     */
    public function testNoRouteException()
    {
        // prepare container
        $routes = $this->prepareRoutes(1);

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/noroute");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();

        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn($routes[0], false);

        $this->_logger->expects($this->exactly(2))
            ->method("info");

        $this->_logger->expects($this->once())
            ->method("error");

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        $this->expectException(
            \SlaxWeb\Router\Exception\RouteNotFoundException::class
        );
        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Test Special URI Matchers
     *
     * Tests that the special '[:params:]' and '[:named:]' match keywords work
     * as expected. Both should match anything non-greedy and push found
     * parameters to the Request object.
     *
     * @return void
     */
    public function testSpecialUriMatchers()
    {
        // prepare container
        $routes = $this->prepareRoutes(1, function($count) {
            return "test/[:params:]/named/[:named:]";
        });

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/test/param1/param2/named/param1/value1");

        $request->expects($this->once())
            ->method("addQuery")
            ->withConsecutive(
                [["parameters" => ["param1", "param2"], "param1" => "value1"]]
            );

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();

        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn($routes[0], false);

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Test Default Route
     *
     * Ensure that an empty path info is properly translated to the default route
     * and the dispatcher matches the Route definition with the default route.
     *
     * @return void
     */
    public function testDefaultRoute()
    {
        $routeMock = $this->getMockBuilder("\\SlaxWeb\\Router\\Route")
            ->setMethods(null)
            ->getMock();
        $routeMock->set(
            "something-to-match-only-through-isDefault",
            \SlaxWeb\Router\Route::METHOD_GET,
            function (
                \SlaxWeb\Router\Request $request,
                \SlaxWeb\Router\Response $response,
                $tester
            ) {
                $tester->call("method", 1);
            },
            true
        );

        // prepare container
        $this->_container->expects($this->once())
            ->method("defaultRoute")
            ->willReturn($routeMock);

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->once())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->once())
            ->method("getPathInfo")
            ->willReturn("/");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("method", 1);

        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Test multiple URI matches
     *
     * Test that router correctly dispatches a request when multiple URIs are
     * found in the route definition regex separated by OR(|).
     *
     * @return void
     */
    public function testMultipleURIMatches()
    {
        $route = new \SlaxWeb\Router\Route;
        $route->set("uri1$|^uri2|uri3", \SlaxWeb\Router\Route::METHOD_GET, function (
            \SlaxWeb\Router\Request $request,
            \SlaxWeb\Router\Response $response,
            $tester
        ) {
            $tester->call($request->getPathInfo());
        }, true);

        // prepare container
        $this->_container->expects($this->any())
            ->method("next")
            ->will(
                $this->onConsecutiveCalls($route, $route, $route, false)
            );

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->will(
                $this->onConsecutiveCalls("/uri1", "/uri1", "/uri2", "/uri2", "/uri3", "/uri3")
            );

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->exactly(3))
            ->method("call")
            ->withConsecutive(
                ["/uri1"],
                ["/uri2"],
                ["/uri3"]
            );

        $dispatcher->dispatch($request, $response, $tester);
        $dispatcher->dispatch($request, $response, $tester);
        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Multiple Method Route
     *
     * Ensure the dispatcher functions properly when multiple HTTP Methods are defined
     * for a single route
     *
     * @return void
     */
    public function testMultiMethodRoute()
    {
        $route = new \SlaxWeb\Router\Route;
        $route->set("uri", \SlaxWeb\Router\Route::METHOD_GET | \SlaxWeb\Router\Route::METHOD_POST, function (
            \SlaxWeb\Router\Request $request,
            \SlaxWeb\Router\Response $response,
            $tester
        ) {
            $tester->call();
        }, true);

        // prepare container
        $this->_container->expects($this->any())
            ->method("next")
            ->will(
                $this->onConsecutiveCalls($route, $route)
            );

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->will(
                $this->onConsecutiveCalls("GET", "POST")
            );

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("uri");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->exactly(2))
            ->method("call");

        $dispatcher->dispatch($request, $response, $tester);
        $dispatcher->dispatch($request, $response, $tester);
    }

    /*
     * Test Segment Based URI matching
     *
     * Ensure the Segment Based URI matching executes the default method in the
     * controller, the URI provided method, that it will ignore URI prepends and
     * try to match by segments only URIs that begin with the prepend, and that
     * further URI segments will be converted to controller method parameters.
     *
     * The above tests are broken down into three methods bellow, each dependant
     * on the former.
     */
    public function testSegmentBasedDefaultMethod()
    {
        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // prepare container
        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn(false);

        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("MockController");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("customDefault");

        $dispatcher->enableSegMatch(
            "\\SlaxWeb\\Router\\Tests\\Unit",
            [$tester],
            "",
            "customDefault"
        );

        $dispatcher->dispatch($request, $response, $tester);

        return $dispatcher;
    }

    /**
     * @depends testSegmentBasedDefaultMethod
     */
    public function testSegmentBasedPrepend($dispatcher)
    {
        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->will(
                $this->onConsecutiveCalls(
                    "uri/prepend/MockController",
                    "falsePrepend/MockController"
                )
            );

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("customDefault");

        $dispatcher->enableSegMatch(
            "\\SlaxWeb\\Router\\Tests\\Unit",
            [$tester],
            "uri/prepend/",
            "customDefault"
        );

        $dispatcher->dispatch($request, $response);
        try {
            $dispatcher->dispatch($request, $response, $tester);
        } catch (\SlaxWeb\Router\Exception\RouteNotFoundException $e) {}

        return $dispatcher;
    }

    /**
     * @depends testSegmentBasedDefaultMethod
     */
    public function testSegmentBasedMethod($dispatcher)
    {
        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("MockController/uriMethod");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("uriMethod");

        $dispatcher->enableSegMatch("\\SlaxWeb\\Router\\Tests\\Unit", [$tester]);

        $dispatcher->dispatch($request, $response);

        return $dispatcher;
    }

    /**
     * @depends testSegmentBasedMethod
     */
    public function testSegmentBasedParams($dispatcher)
    {
        // mock the request, response, and a special tester mock
        $request = $this->createMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("MockController/methodWithParams/param");

        $response = $this->createMock(
            "\\SlaxWeb\\Router\\Response"
        );

        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("methodWithParams", "param");

        $dispatcher->enableSegMatch("\\SlaxWeb\\Router\\Tests\\Unit", [$tester]);

        $dispatcher->dispatch($request, $response);
    }

    /**
     * Prepare routes
     *
     * Prepare some fake routes for tests.
     *
     * @param int $amount Amount of Routes to return
     * @param callable $uriCallback Callback to get custom URI, defaults to null
     * @return array
     */
    protected function prepareRoutes(int $amount = 6, callable $uriCallback = null)
    {
        $routeMock = $this->createMock("\\SlaxWeb\\Router\\Route");
        $routeMock = $this->getMockBuilder("\\SlaxWeb\\Router\\Route")
            ->setMethods(null)
            ->getMock();
        $routes = [];
        $methods = [
            \SlaxWeb\Router\Route::METHOD_GET,
            \SlaxWeb\Router\Route::METHOD_POST,
            \SlaxWeb\Router\Route::METHOD_PUT,
            \SlaxWeb\Router\Route::METHOD_DELETE,
            \SlaxWeb\Router\Route::METHOD_CLI,
            \SlaxWeb\Router\Route::METHOD_ANY
        ];
        for ($count = 0; $count < $amount; $count++) {
            $method = $count > (count($methods) - 1)
                ? $methods[$count % count($methods)]
                : $methods[$count];

            $route = clone $routeMock;
            $uri = "uri" . ($count + 1);
            if ($uriCallback !== null) {
                $uri = $uriCallback($count);
            }
            $route->set(
                $uri,
                $method,
                function (
                    \SlaxWeb\Router\Request $request,
                    \SlaxWeb\Router\Response $response,
                    $tester
                ) use (
                    $count,
                    $method
                ) {
                    $tester->call($method, $count);
                },
                true
            );
            $routes[] = $route;
        }

        return $routes;
    }
}
