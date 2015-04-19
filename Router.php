<?php
namespace SlaxWeb\Router;

use \SlaxWeb\Router\Exceptions as E;

class Router
{
    protected $_method = "GET";
    protected $_name = "";
    protected $_action = null;
    protected $_paramCount = 0;
    protected $_routes = [];
    protected $_request = [];
    protected $_params = [];
    protected $_routed = [];

    public function __construct(array $options)
    {
        if (isset($options["uri"]) === false || $options["uri"] === null) {
            $options["uri"] = "/";
        }
        if (isset($options["method"]) === false || $options["method"] === null) {
            $options["method"] = "GET";
        }
        $this->_request = $options;
        $this->_prepareUri();
    }

    public function defaultRoute()
    {
        $this->_name = "*";
        return $this;
    }

    public function get()
    {
        $this->_method = "GET";
        return $this;
    }

    public function post()
    {
        $this->_method = "POST";
        return $this;
    }

    public function put()
    {
        $this->_method = "PUT";
        return $this;
    }

    public function delete()
    {
        $this->_method = "DELETE";
        return $this;
    }

    public function cli()
    {
        $this->_method = "CLI";
        return $this;
    }

    public function name($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function action($action)
    {
        $this->_action = $action;
        return $this;
    }

    public function params($count)
    {
        $this->_paramCount = $count;
        return $this;
    }

    public function store()
    {
        if (empty($this->_name) === true) {
            throw new E\NoNameException("Route needs a name", 500);
        }
        if (empty($this->_action) === true) {
            throw new E\NoActionException("Route needs an action", 500);
        }

        $this->_routes[$this->_method][$this->_name] = ["action" => $this->_action, "params" => $this->_paramCount];

        $this->_method = "GET";
        $this->_name = "";
        $this->_action = null;
        $this->_paramCount = 0;

        return $this;
    }

    public function process()
    {
        if (isset($this->_routes[$this->_request["method"]]) === false) {
            $this->_throwNoRouteException($this->_request);
        }
        $routeData = $this->_routes[$this->_request["method"]];
        $action = null;
        $uri = "";
        $route = "";
        $params = "";
        foreach ($routeData as $r => $a) {
            if (strpos($this->_request["uri"], $r) === 0) {
                $action = $a["action"];
                $uri = $this->_request["uri"];
                $route = $r;
                $params = $a["params"];
                break;
            }
        }
        if ($action === null && isset($routeData["*"])) {
            $action = $routeData["*"]["action"];
            $uri = $this->_request["uri"];
            $route = "*";
            $params = $routeData["*"]["params"];
        }
        if ($action === null) {
            $this->_throwNoRouteException($this->_request);
        }
        $reqParams = substr($uri, strlen($route) + 1);
        if ($reqParams !== false) {
            $this->_params = explode("/", substr($uri, strlen($route) + 1));
        }
        if (count($this->_params) < $params) {
            $this->_throwNoRouteException($this->_request);
        }
        $this->_routed = [
            "uri"       =>  $uri,
            "action"    =>  $action,
            "params"    =>  $params
        ];
        return [
            "action"    =>  $routeData[$route]["action"],
            "params"    =>  $this->_params
        ];
    }

    public function getRouted()
    {
        return $this->_routed;
    }

    protected function _prepareUri()
    {
        $uri = $this->_request["uri"];
        if (strpos($uri, "/index.php") !== false) {
            $uri = ltrim($uri, "/index.php") . "/";
        }
        if ($uri !== "/") {
            $uri = ltrim($uri, "/");
        }
        $this->_request["uri"] = $uri === "/" ? $uri : rtrim($uri, "/");
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
