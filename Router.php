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

    public function __construct(array $options)
    {
        if (empty($options["uri"]) === true) {
            $options["uri"] = "/";
        }
        if (empty($options["method"]) === true) {
            $options["method"] = "GET";
        }
        $this->_request = $options;
        $this->prepareUri();
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

    public function test()
    {
        var_dump($this->_routes);
    }

    public function process()
    {
        if (isset($this->_routes[$this->_request["method"]]) === false) {
            $this->_throwNoRouteException($this->_request);
        }
        $action = null;
        $uri = "";
        $route = "";
        $params = "";
        foreach ($this->_routes[$this->_request["method"]] as $r => $a) {
            if (strpos($this->_request["uri"], $r) === 0) {
                $action = $a["action"];
                $uri = $this->_request["uri"];
                $route = $r;
                $params = $a["params"];
                break;
            }
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
        return [
            "action"    =>  $this->_routes[$this->_request["method"]][$route]["action"],
            "params"    =>  $this->_params
        ];
    }

    protected function prepareUri()
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
