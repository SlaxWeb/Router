<?php
/**
 * Route Tests
 *
 * Ensures that the Route class provides a method for setting route options, and
 * they can be retrieved.
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

class RouteTest extends \PHPUnit_Framework_TestCase
{
    use \Codeception\Specify;

    /**
     * Route
     *
     * @var \SlaxWeb\Router\Route
     */
    protected $_router = null;

    /**
     * Prepare test
     *
     * Prepare the Route object for testing.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->_route = new Route;
    }

    protected function tearDown()
    {
    }

    /**
     * Test setter works
     *
     * Make sure that the setter works and accepts correct parameters. And that
     * the passed in parameters are stored properly.
     *
     * @return void
     */
    public function testSetterOk()
    {
        $this->_route->set("uri", Route::METHOD_GET, function () {
            return true;
        });

        $this->specify("URI is stored in regex format", function () {
            $this->assertEquals(
                "~^uri$~",
                $this->_route->uri,
                "URI is not in regex format"
            );
        });

        $this->specify("HTTP Method is stored", function () {
            $this->assertEquals(
                Route::METHOD_GET,
                $this->_route->method,
                "HTTP Method not stored"
            );
        });

        $this->specify("Action is stored", function () {
            $this->assertTrue(
                ($this->_route->action)(),
                "Action did not return expected value"
            );
        });

        $this->specify("Non-default route", function () {
            $this->assertFalse(
                $this->_route->isDefault,
                "Defined route is not non-default route"
            );

        });
    }

    /**
     * Test default route
     *
     * Ensure that the 4th parameter controls the default route property.
     *
     * @return void
     */
    public function testDefaultRoute()
    {
        $this->_route->set("uri", Route::METHOD_GET, function () {
            return true;
        }, true);

        $this->specify("Default route", function () {
            $this->assertTrue(
                $this->_route->isDefault,
                "Defined route is not default route"
            );

        });
    }

    /**
     * Test setter failure
     *
     * Test that the setter will raise an Exception when the retrieved HTTP
     * method is not one of the expected values.
     *
     * @return void
     */
    public function testSetterInvalidMethodException()
    {
        $this->specify(
            "Setter throws exception on invalid method",
            function () {
                $this->_route->set("uri", 12345, function () {
                    return false;
                });
            },
            ["throws" => "SlaxWeb\\Router\\Exception\\InvalidMethodException"]
        );
    }

    /**
     * Test getter failure
     *
     * Test that the getter will raise an Exception when an attempt is made to
     * access an unknown property.
     *
     * @return void
     */
    public function testGetterUnknownProperty()
    {
        $this->specify(
            "Getter throws exception on unknown property",
            function () {
                $this->_route->unknown;
            },
            ["throws" => "SlaxWeb\\Exception\\UnknownPropertyException"]
        );
    }

    /**
     * Test set 404 Route
     *
     * Test that the Route class can set the 404 Route successfully, and that
     * it sets the correct values.
     *
     * @return void
     */
    public function testSet404Route()
    {
        $this->_route->set404Route(function () {
            return true;
        });

        $this->specify(
            "404 Route set successfully",
            function () {
                $this->assertEquals("404RouteNotFound", $this->_route->uri);
                $this->assertEquals(Route::METHOD_ANY, $this->_route->method);
                $this->assertTrue(($this->_route->action)());
            }
        );
    }
}
