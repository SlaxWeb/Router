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
        $routes = $this->_prepareRoutes();

        $this->_container->expects($this->exactly(3))
            ->method("next")
            ->will(
                $this->onConsecutiveCalls($routes[0], $routes[1], $routes[2])
            );

        $dispatcher = new Dispatcher(
            $this->_container,
            $this->_hooks,
            $this->_logger
        );

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

        $tester = $this->getMockBuilder("FakeTesterMock")
            ->setMethods(["call"])
            ->getMock();
        $tester->expects($this->once())
            ->method("call")
            ->with("PUT", 2);


        $dispatcher->dispatch($request, $response, $tester);
    }

    /**
     * Prepare routes
     *
     * Prepare some fake routes for tests.
     *
     * @return array
     */
    protected function _prepareRoutes()
    {
        $routeMock = $this->getMock("\\SlaxWeb\\Router\\Route");
        $routes = [];
        $methods = ["GET", "POST", "PUT", "DELETE", "CLI", "ANY"];
        for ($count = 0; $count < 6; $count++) {
            $route = clone $routeMock;
            $route->uri = "~^uri" . ($count + 1) . "$~";
            $route->method = $methods[$count];
            $route->action = function (
                \SlaxWeb\Router\Request $request,
                \Symfony\Component\HttpFoundation\Response $response,
                $tester
            ) use ($count, $methods) {
                $tester->call($methods[$count], $count);
            };
            $routes[] = $route;
        }

        return $routes;
    }
}
