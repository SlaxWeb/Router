<?php
/**
 * Request class Tests
 *
 * The Request class must extend the Symfony\Component\HttpFoundation\Request
 * class and provide an additional 'addQuery' method for adding parameters to
 * the query parameters. This test ensures that this method functions properly.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router\Tests\Unit;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    use \Codeception\Specify;

    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    /**
     * Test add to Query Parameters
     *
     * Ensure that parameters can be added to the Query Parameters of the
     * \Symfony\Component\HttpFoundation\Request object.
     *
     * @return void
     */
    public function testAddToQueryParams()
    {
        $request = new \SlaxWeb\Router\Request(["uri" => "uri value"]);

        $this->specify(
            "Add more parameters to query params",
            function () use ($request) {
                $request->addQuery(["new" => "new value"]);
                $this->assertEquals("new value", $request->get("new"));
            }
        );
    }
}
