<?php
/**
 * Container Tests
 *
 * Tests for the Container class of the Router component. The Container needs to
 * store retrieved Route definitions in an internal protected property, and
 * provide a way to retrieve those definitions. This test ensures that this
 * functionality works as intentended.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router\Tests\Unit;

use SlaxWeb\Router\Route;
use SlaxWeb\Router\Container;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    use \Codeception\Specify;

    /**
     * Container
     *
     * @var \SlaxWeb\Router\Container
     */
    protected $container = null;

    /**
     * Route Mock
     *
     * @var mocked object
     */
    protected $route = null;

    /**
     * Logger Mock
     *
     * @var mocked object
     */
    protected $logger = null;

    /**
     * Prepare test
     *
     * Prepare a fresch container object for every test as well as a fresh Route
     * mock that can be cloned in each test if multiple routes are required.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->logger = $this->createMock("\\Psr\\Log\\LoggerInterface");

        $this->container = new Container($this->logger);

        $this->route = $this->getMockBuilder("\\SlaxWeb\\Router\\Route")
            ->setMethods(null)
            ->getMock();
    }

    protected function tearDown()
    {
    }

    /**
     * Test Only Valid Route Accepted
     *
     * Ensure that the 'add' method accepts only a valid Route object, and
     * propper Route data has been set.
     *
     * @return void
     */
    public function testOnlyValidRouteAccepted()
    {
        $exception = false;
        try {
            $this->container->add(new \stdClass);
        } catch (\TypeError $e) {
            $exception = true;
        }
        if ($exception === false) {
            throw new \Exception("'TypeError' was expected but was not thrown");
        }

        $route = clone $this->route;

        $logger = clone $this->logger;
        $logger->expects($this->once())
            ->method("error");
        $logger->expects($this->once())
            ->method("debug");
        $container = clone $this->container;
        $this->specify(
            "Route definition incomplete",
            function () use ($route, $container) {
                $container->add($route);
            },
                ["throws" => "SlaxWeb\\Router\\Exception\\RouteIncompleteException"]
            );

        $logger = clone $this->logger;
        $logger->expects($this->once())
            ->method("info");
        $container = clone $this->container;
        $this->specify("Valid Route", function () use ($route, $container) {
            $route->set(
                "uri",
                0b1,
                function () {
                    return true;
                }
            );
            $container->add($route);
        });
    }

    /**
     * Test Route retrieval
     *
     * Ensure that all inserted Route definitions can be retrieved, as an array,
     * as well as that individual Route definitions can be obtained with 'next'
     * and 'prev' methods. If 'next' is called for the first time, then the
     * first Route is returned, and the same applies for 'prev'.
     *
     * @return void
     * @depends testOnlyValidRouteAccepted
     */
    public function testRouteRetrieval()
    {
        for ($count = 0; $count < 5; $count++) {
            $route = clone $this->route;

            $route->set(
                "uri" . ($count + 1),
                0b1,
                function () use ($count) {
                    return $count;
                }
            );
            $this->container->add($route);
        }

        $this->specify("All definitons retrieved", function () {
            $count = 0;
            foreach ($this->container->getAll() as $route) {
                $this->assertEquals($count++, ($route->action)());
                $this->assertEquals(0b1, $route->method);
                $this->assertRegExp($route->uri, "uri{$count}");
            }
        });

        $this->specify(
            "'next' returns first Route on first call",
            function () {
                $route = $this->container->next();
                $this->assertEquals(0, ($route->action)());
                $this->assertEquals(0b1, $route->method);
                $this->assertRegExp($route->uri, "uri1");
            }
        );

        $this->specify(
            "'prev' returns last Route on first call",
            function () {
                $route = $this->container->prev();
                $this->assertEquals(4, ($route->action)());
                $this->assertEquals(0b1, $route->method);
                $this->assertRegExp($route->uri, "uri5");
            }
        );

        $this->specify(
            "Itteration with 'next' possible",
            function () {
                $count = 0;
                while ($route = $this->container->next()) {
                    $this->assertEquals($count++, ($route->action)());
                    $this->assertEquals(0b1, $route->method);
                    $this->assertRegExp($route->uri, "uri{$count}");
                }
            }
        );

        $this->specify(
            "Itteration with 'prev' possible",
            function () {
                $count = 5;
                while ($route = $this->container->prev()) {
                    $this->assertRegExp($route->uri, "uri" . $count--);
                    $this->assertEquals(0b1, $route->method);
                    $this->assertEquals($count, ($route->action)());
                }
            }
        );
    }

    public function testDefaultRoute()
    {
        $route = clone $this->route->set(
            "default_route1",
            0b1,
            function () {},
            true
        );
        $this->container->add($route);
        $route = clone $this->route->set(
            "default_route2",
            0b1,
            function () {},
            true
        );
        $this->container->add($route);
        $this->specify(
            "'defaultRoute' returns the first default route",
            function () {
                $route = $this->container->defaultRoute();
                $this->assertInstanceOf(\SlaxWeb\Router\Route::class, $route);
                $this->assertRegExp($route->uri, "default_route1");
            }
        );
    }

    public function test404Route()
    {
        $route = clone $this->route->set404Route(function () {});
        $this->container->add($route);
        $this->specify(
            "'get404Route' returns the first 404 route",
            function () {
                $route = $this->container->get404Route();
                $this->assertInstanceOf(\SlaxWeb\Router\Route::class, $route);
                $this->assertEquals($route->uri, "404RouteNotFound");
            }
        );
    }
}
