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

use Mockery as m;
use SlaxWeb\Router\Route;
use SlaxWeb\Router\Container;

class ContainerTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Container
     *
     * @var \SlaxWeb\Router\Container
     */
    protected $_container = null;

    /**
     * Route Mock
     *
     * @var mocked object
     */
    protected $_route = null;

    /**
     * Prepare test
     *
     * Prepare a fresch container object for every test as well as a fresh Route
     * mock that can be cloned in each test if multiple routes are required.
     *
     * @return void
     */
    protected function _before()
    {
        $this->_container = new Container;

        $this->_route = m::mock("\\SlaxWeb\\Router\\Route")->makePartial();
        $this->_route->uri = "";
        $this->_route->method = "";
        $this->_route->action = null;
    }

    protected function _after()
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
            $this->_container->add(new \stdClass);
        } catch (\TypeError $e) {
            $exception = true;
        }
        if ($exception === false) {
            throw new \Exception("'TypeError' was expected but was not thrown");
        }

        $route = clone $this->_route;

        $this->specify(
            "Route definition incomplete",
            function () use ($route) {
                $this->_container->add($route);
            },
            ["throws" => "SlaxWeb\\Router\\Exception\\RouteIncompleteException"]
        );

        $this->specify("Valid Route", function () use ($route) {
            $route->uri = "~^uri$~";
            $route->method = "GET";
            $route->action = function () {
                return true;
            };
            $this->_container->add($route);
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
            $route = clone $this->_route;

            $route->uri = "~^uri" . ($count + 1) ."$~";
            $route->method = "GET";
            $route->action = function () use ($count) {
                return $count;
            };
            $this->_container->add($route);
        }

        $this->specify("All definitons retrieved", function () {
            $count = 0;
            foreach ($this->_container->getAll() as $route) {
                $this->assertEquals($count++, call_user_func($route->action));
                $this->assertEquals("GET", $route->method);
                $this->assertRegExp($route->uri, "uri{$count}");
            }
        });

        $this->specify(
            "'next' returns first Route on first call",
            function () {
                $route = $this->_containter->next();
                $this->assertEquals(0, call_user_func($route->action));
                $this->assertEquals("GET", $route->method);
                $this->assertRegExp($route->uri, "uri1");
            }
        );

        $this->specify(
            "'prev' returns last Route on first call",
            function () {
                $route = $this->_containter->prev();
                $this->assertEquals(2, call_user_func($route->action));
                $this->assertEquals("GET", $route->method);
                $this->assertRegExp($route->uri, "uri3");
            }
        );

        $this->specify(
            "Itteration with 'next' possible",
            function () {
                $count = 0;
                while ($this->_container->next()) {
                    $this->assertEquals($count++, call_user_func($route->action));
                    $this->assertEquals("GET", $route->method);
                    $this->assertRegExp($route->uri, "uri{$count}");
                }
            }
        );

        $this->specify(
            "Itteration with 'prev' possible",
            function () {
                $count = 3;
                while ($this->_container->prev()) {
                    $this->assertRegExp($route->uri, "uri" . $count--);
                    $this->assertEquals("GET", $route->method);
                    $this->assertEquals($count, call_user_func($route->action));
                }
            }
        );
    }
}