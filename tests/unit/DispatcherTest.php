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
        $this->_logger = $this->getMock("\\Psr\\Log\\LoggerInterface");
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

        $this->_hooks->expects($this->once())
            ->method("exec")
            ->with("router.dispatcher.afterInit");

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
        $routes = $this->_prepareRoutes();
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
        $request = $this->getMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->once())
            ->method("getMethod")
            ->willReturn("PUT");

        $request->expects($this->once())
            ->method("getPathInfo")
            ->willReturn("/uri3");

        $response = $this->getMock(
            "\\Symfony\\Component\\HttpFoundation\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("PUT", 2);

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
        $routes = $this->_prepareRoutes(1);
        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn($routes[0]);

        // prepare hooks
        $this->_hooks->expects($this->exactly(10))
            ->method("exec")
            ->withConsecutive(
                // normal execution
                ["router.dispatcher.afterInit"],
                ["router.dispatcher.routeFound", $routes[0]],
                ["router.dispatcher.beforeDispatch", $routes[0]],
                ["router.dispatcher.afterDispatch"],
                // stop by returning bool(false)
                ["router.dispatcher.routeFound", $routes[0]],
                ["router.dispatcher.beforeDispatch", $routes[0]],
                ["router.dispatcher.afterDispatch"],
                // stop by returning [bool(false)]
                ["router.dispatcher.routeFound", $routes[0]],
                ["router.dispatcher.beforeDispatch", $routes[0]],
                ["router.dispatcher.afterDispatch"]
            )->will(
                $this->onConsecutiveCalls(
                    // normal execution
                    null,
                    null,
                    "some return value",
                    null,
                    // stop by returning bool(false)
                    null,
                    false,
                    null,
                    // stop by returning [bool(false)]
                    null,
                    [false],
                    null
                )
            );

        $this->_logger->expects($this->exactly(8))
            ->method("info");

        // init the dispatcher
        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

        // mock the request, response, and a special tester mock
        $request = $this->getMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/uri1");

        $response = $this->getMock(
            "\\Symfony\\Component\\HttpFoundation\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("GET", 0);

        // normal execution
        $dispatcher->dispatch($request, $response, $tester);
        // stopped execution through hooks return value bool(false)
        $dispatcher->dispatch($request, $response, $tester);
        // stopped execution through hooks return value [bool(false)]
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
        $routes = $this->_prepareRoutes(1);

        // mock the request, response, and a special tester mock
        $request = $this->getMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/noroute");

        $response = $this->getMock(
            "\\Symfony\\Component\\HttpFoundation\\Response"
        );

        // used to see what exactly gets passed to route actions
        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with(404);

        $routes[] = clone $routes[0];
        $routes[1]->uri = "404RouteNotFound";
        $routes[1]->method = "ANY";
        $routes[1]->action = function (
            \SlaxWeb\Router\Request $request,
            \Symfony\Component\HttpFoundation\Response $response,
            $tester
        ) {
            $tester->call(404);
        };

        $this->_container->expects($this->any())
            ->method("next")
            ->willReturn($routes[0], $routes[1], false);

        // prepare hooks
        $this->_hooks->expects($this->exactly(4))
            ->method("exec")
            ->withConsecutive(
                ["router.dispatcher.afterInit"],
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
        $routes = $this->_prepareRoutes(1);

        // mock the request, response, and a special tester mock
        $request = $this->getMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/noroute");

        $response = $this->getMock(
            "\\Symfony\\Component\\HttpFoundation\\Response"
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
     * @todo Enable test when functionality is available
     *
     * @return void
     */
    public function _testSpecialUriMatchers()
    {
        // prepare container
        $routes = $this->_prepareRoutes(1);
        $routes[0]->uri = "test/[:params:]/named/[:named:]";

        // mock the request, response, and a special tester mock
        $request = $this->getMock("\\SlaxWeb\\Router\\Request");
        $request->expects($this->any())
            ->method("getMethod")
            ->willReturn("GET");

        $request->expects($this->any())
            ->method("getPathInfo")
            ->willReturn("/test/param1/param2/named/param1/value1");

        $request->expects($this->exactly(2))
            ->method("addQuery")
            ->withConsecutive(
                ["parameters" => ["param1", "param2"], "param1" => "value1"]
            );

        $response = $this->getMock(
            "\\Symfony\\Component\\HttpFoundation\\Response"
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
     * Prepare routes
     *
     * Prepare some fake routes for tests.
     *
     * @param int $amount Amount of Routes to return
     * @return array
     */
    protected function _prepareRoutes(int $amount = 6)
    {
        $routeMock = $this->getMock("\\SlaxWeb\\Router\\Route");
        $routes = [];
        $methods = ["GET", "POST", "PUT", "DELETE", "CLI", "ANY"];
        for ($count = 0; $count < $amount; $count++) {
            $method = $count > (count($methods) - 1)
                ? $methods[$count % count($methods)]
                : $methods[$count];

            $route = clone $routeMock;
            $route->uri = "~^uri" . ($count + 1) . "$~";
            $route->method = $method;

            $route->action = function (
                \SlaxWeb\Router\Request $request,
                \Symfony\Component\HttpFoundation\Response $response,
                $tester
            ) use (
                $count,
                $method
            ) {
                $tester->call($method, $count);
            };
            $routes[] = $route;
        }

        return $routes;
    }
}
