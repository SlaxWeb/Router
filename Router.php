<?php
/**
 * Request router
 *
 * Main component of the Router library. It stores the defined routes in its
 * internal array, and resolves the request to the defined action.
 *
 * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @package \SlaxWeb\Router
 * @version v0.3
 * @license MIT
 *
 * @copyright (c) 2015 Tomaz Lovrec
 */
namespace SlaxWeb\Router;

use \SlaxWeb\Router\Exceptions as E;

class Router
{
    /**
     * Route method
     *
     * Used for configuring a route before storing to internal array
     *
     * @var string
     */
    protected $_method = "GET";

    /**
     * Route name (URI)
     *
     * Used for configuring a route before storing to internal array
     *
     * @var string
     */
    protected $_name = "";

    /**
     * Route storage
     *
     * Stored routes
     *
     * @var array
     */
    protected $_routes = [];

    /**
     * User request
     *
     * @var \SlaxWeb\Router\Request
     */
    protected $_request = null;

    /**
     * Class constructor
     *
     * Initiate the Router and set the user request
     *
     * @param $request \SlaxWeb\Router\Request User request
     */
    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * Magic call method
     *
     * If the method called is intended to send the request method for
     * the route, this method is simply just set to the '_method' property.
     * In any other case, the requested method is called if it exists.
     * If the called method does not exist an Exception is thrown.
     *
     * @param $method string Method name
     * @param $params array Parameters for the calling method
     *
     * @return Returns self or the return value of the called method
     */
    public function __call($method, $params)
    {
        if (in_array($method, ["get", "post", "put", "delete", "cli"])) {
            $this->_method = strtoupper($method);
            return $this;
        }

        throw new \Exception("Requested method '{$method}' not found.", 500);
    }

    /**
     * Default route
     *
     * Set the default 'catch-all' route.
     *
     * @return Self
     */
    public function defaultRoute()
    {
        $this->_name = "*";
        return $this;
    }

    /**
     * Route URI name
     *
     * Set the URI name for the route to be configured. If the name is not
     * provided as input parameter, an Exception is thrown.
     * 
     * The name has to be either a full URI, or a regex that will match the
     * visitors URI. Each capturing group result is added as a parameter
     * in the parameters list in the return argument of 'process' method.
     *
     * Example: "myroute/(.*)$" will catch "myroute/anythingBehindHere",
     * but not "myroute", and "anythingBehindHere" will be returned to
     * the caller as a parameter list.
     *
     * @param $name string URI name
     *
     * @return Self
     */
    public function name($name)
    {
        if ($name === null) {
            throw new E\InvalidNameException("Route name can not be null", 500);
        }
        $this->_name = $name;
        return $this;
    }

    /**
     * Set route action
     *
     * Set the route action, and store the route to the internal route storage.
     * If the previous required route parameters have not been set, it throws
     * and Exception, of if the action is not callable.
     *
     * Action either has to be a callable closure, or and array containing
     * the full class name and method name. The class has to be loaded before
     * calling this method, otherwise the callable check will fail and produce
     * an Exception.
     *
     * @param $action mixed Either an array holding the class and method name
     * or a closure.
     *
     * @return Self
     */
    public function action($action)
    {
        if ($this->_name === "") {
            throw new E\NoNameException("Route needs a name", 500);
        }
        if (is_callable($action) === false) {
            throw new E\InvalidActionException("Action must be callable", 500);
        }

        $this->_routes[$this->_method][$this->_name] = ["action" => $action];

        $this->_method = "GET";
        $this->_name = "";

        return $this;
    }

    /**
     * Process the request
     *
     * Searches through the stored routes and tries to find a match for
     * the current request. When a first match occures, the loop is broken
     * and the first matchin route is used. HTTP method and request URI
     * have to match for the route to match the request. Once a match is found
     * the action and parameters are returned as an array.
     *
     * If the request does not match any stored routes and Exception is thrown.
     *
     * @return array Route action and parameters
     */
    public function process()
    {
        // If no route is stored with the requests HTTP method, throw Exception
        if (isset($this->_routes[$this->_request->method]) === false) {
            $this->_throwNoRouteException($this->_request);
        }
        $routeData = $this->_routes[$this->_request->method];
        $action = null;
        $params = "";
        foreach ($routeData as $r => $a) {
            $matches = null;
            $r = str_replace("/", "\\/", $r);
            if (in_array(preg_match_all("~^{$r}$~", $this->_request->uri, $matches), [0, false]) === false) {
                foreach ($matches as $m) {
                    $params[] = $m[0];
                }
                $action = $a["action"];
                $params = array_filter($params);
                array_shift($params);
                break;
            }
        }

        if ($action === null) {
            $this->_throwNoRouteException($this->_request);
        }

        return [
            "action"    =>  $action,
            "params"    =>  $params
        ];
    }

    public function getRouted()
    {
        return $this->_routed;
    }

    /**
     * Get user request
     *
     * @return \SlaxWeb\Router\Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * No Route Exception
     *
     * Exception helper method.
     * 
     * @param $request \SlaxWeb\Router\Request User request
     */
    protected function _throwNoRouteException($request)
    {
        throw new E\RouteNotFoundException(
            "No route could be found for this request",
            404,
            $request
        );
    }
}
