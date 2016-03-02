<?php
/**
 * Route class of Router component
 *
 * The Route class is responsible for maintaining the internal routes definition
 * container, and matching of the received request with a stored route
 * definition, and executing said definition.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Route
{
    /**
     * Request component
     *
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $_request = null;

    /**
     * Response component
     *
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $_response = null;

    /**
     * Class constructor
     *
     * Store the retrieved Request and Response objects to local protected
     * properties.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->_request = $request;
        $this->_response = $response;
    }
}
