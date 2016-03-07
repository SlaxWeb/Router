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
}
