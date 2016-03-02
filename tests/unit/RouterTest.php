<?php
/**
 * Router Tests
 *
 * Ensures that the Router component main class functions as intended.
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

class RouterTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Request Mock
     *
     * @var mock object
     */
    protected $_request = null;

    /**
     * Response Mock
     *
     * @var mock object
     */
    protected $_response = null;

    /**
     * Preapare the tests
     *
     * Instantiate the Request and Response classes
     *
     * @return void
     */
    protected function _before()
    {
        $this->_request = m::mock(
            "\\Symfony\\Component\\HttpFoundation\\Request"
        );
        $this->_response = m::mock(
            "\\Symfony\\Component\\HttpFoundation\\Response"
        );
    }

    protected function _after()
    {
    }

    /**
     * Test constructor
     *
     * Test the constructor, make sure it accepts the request and response
     * objects of the Symfony Http Foundation.
     *
     * @var void
     */
    public function testConstructor()
    {
        m::mock("\\SlaxWeb\\Router\\Route", [$this->_request, $this->_response]);
    }
}
