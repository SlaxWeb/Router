<?php
/**
 * Response class Tests
 *
 * The Request class must extend the Symfony\Component\HttpFoundation\Request
 * class and provide an additional 'addContent' method for appending content to
 * existing content. This test ensures that this method functions properly.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router\Tests\Unit;

class ResponseTest extends \PHPUnit_Framework_TestCase
{
    use \Codeception\Specify;

    protected function setUp()
    {
    }

    protected function tearDown()
    {
    }

    /**
     * Test add content
     *
     * Ensure that add content really adds to content of the Response object.
     *
     * @return void
     */
    public function testAddContent()
    {
        $response = new \SlaxWeb\Router\Response();

        $this->specify(
            "Add content to response",
            function () use ($response) {
                $response->setContent("Existing Content");
                $response->addContent(" New Content");
                $this->assertEquals(
                    "Existing Content New Content",
                    $response->getContent()
                );
            }
        );
    }
}
