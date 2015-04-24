<?php
namespace SlaxWeb\Router;

use \SlaxWeb\Router\Exceptions as E;

class Router
{
    protected $_method = "GET";
    protected $_name = "";
    protected $_action = null;
    protected $_routes = [];
    protected $_request = null;
    protected $_routed = [];

    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    public function __call($method, $params)
    {
        if (in_array($method, ["get", "post", "put", "delete", "cli"])) {
            $this->_method = strtoupper($method);
            return $this;
        }
        if (method_exists($this, $method)) {
            return $this->{$method}(...$params);
        }

        throw new \Exception("Method not found in \\SlaxWeb\\Router\\Router", 500);
    }

    public function defaultRoute()
    {
        $this->_name = "*";
        return $this;
    }

    public function name($name)
    {
        if ($name === null) {
            throw new E\InvalidNameException("Route name can not be null", 500);
        }
        $this->_name = $name;
        return $this;
    }

    public function action($action)
    {
        $this->_action = $action;
        if ($this->_name === "") {
            throw new E\NoNameException("Route needs a name", 500);
        }
        if (is_callable($action) === false) {
            throw new E\InvalidActionException("Action must be callable", 500);
        }

        $this->_routes[$this->_method][$this->_name] = ["action" => $this->_action];

        $this->_method = "GET";
        $this->_name = "";
        $this->_action = null;

        return $this;
    }

    public function process()
    {
        if (isset($this->_routes[$this->_request->method]) === false) {
            $this->_throwNoRouteException($this->_request);
        }
        $routeData = $this->_routes[$this->_request->method];
        $action = null;
        $uri = "";
        $route = "";
        $params = "";
        foreach ($routeData as $r => $a) {
            $matches = null;
            $r = str_replace("/", "\\/", $r);
            if (in_array(preg_match_all("~^{$r}$~", $this->_request->uri, $matches), [0, false]) === false) {
                foreach ($matches as $m) {
                    $params[] = $m[0];
                }
                $action = $a["action"];
                $uri = array_shift($params);
                $route = $r;
                $params = array_filter($params);
                break;
            }
        }

        if ($action === null) {
            $this->_throwNoRouteException($this->_request);
        }

        $this->_routed = [
            "uri"       =>  $uri,
            "action"    =>  $action,
            "params"    =>  $params
        ];

        return [
            "action"    =>  $action,
            "params"    =>  $params,
            "callable"  =>  is_array($action) === false
        ];
    }

    public function getRouted()
    {
        return $this->_routed;
    }

    protected function _throwNoRouteException($request)
    {
        throw new E\RouteNotFoundException(
            "No route could be found for this request",
            500,
            $request
        );
    }
}
