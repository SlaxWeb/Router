<?php
class RouterTest extends PHPUnit_Framework_TestCase
{
    protected $_counter = 0;

    public function testMagicCall()
    {
        $request = $this->getMockBuilder("\\SlaxWeb\\Router\\Request")
            ->setMethods(["__get"])
            ->getMock();

        $request->method("__get")
            ->will($this->returnCallback([$this, "requestGetCallback"]));

        $router = new \SlaxWeb\Router\Router($request);

        // only get, post, put, delete, cli, and normally existing class methods can be called
        // non existing methods must raise an exception
        $this->assertEquals($router, $router->get());
        $this->assertEquals($router, $router->defaultRoute());
        $this->setExpectedException("\\Exception", "Requested method 'unknownMethod' not found.");
        $router->unknownMethod();
    }

    public function testRoutes()
    {
        $request = $this->getMockBuilder("\\SlaxWeb\\Router\\Request")
            ->setMethods(["__get"])
            ->getMock();

        $request->method("__get")
            ->will($this->returnCallback([$this, "requestGetCallback"]));

        // test a simple request uri
        $router = new \SlaxWeb\Router\Router($request);

        // add three routes and check if the correct one is returned
        $this->setRoutes($router);

        $this->assertEquals(["action" => ["SomeClass", "SomeMethod"], "params" => []], $router->process());

        // test uri with mandatory parameters
        $this->_counter = 1;
        $router = new \SlaxWeb\Router\Router($request);

        // add three routes and check if the correct one is returned
        $this->setRoutes($router);

        $this->assertEquals(["action" => ["SomeClass", "ParamMethod"], "params" => ["param"]], $router->process());

        // test uri with optional parameter - missing
        $this->_counter = 2;
        $router = new \SlaxWeb\Router\Router($request);

        // add three routes and check if the correct one is returned
        $this->setRoutes($router);

        $this->assertEquals(["action" => ["SomeClass", "OptionalMethod"], "params" => []], $router->process());

        // test uri with optional parameter - present
        $this->_counter = 3;
        $router = new \SlaxWeb\Router\Router($request);

        // add three routes and check if the correct one is returned
        $this->setRoutes($router);

        $this->assertEquals(["action" => ["SomeClass", "OptionalMethod"], "params" => ["param"]], $router->process());

        // test uri with optional parameter - present
        $this->_counter = 4;
        $router = new \SlaxWeb\Router\Router($request);

        // add three routes and check if the correct one is returned
        $this->setRoutes($router);

        $this->setExpectedException(
            "\\SlaxWeb\\Router\\Exceptions\\RouteNotFoundException",
            "No route could be found for this request"
        );
        $router->process();
    }

    public function requestGetCallback($param)
    {
        $uris = ["test/uri", "test/param/param", "test/optional/param", "test/optional/param/param", "missing/route"];
        switch ($param)
        {
            case "uri":
                return $uris[$this->_counter];
            case "method":
                return "GET";
            default:
                return false;
        }
    }

    public function setRoutes($router)
    {
        $router->get()->name("test/uri")->action(["SomeClass", "SomeMethod"]);
        $router->get()->name("test/param/(.*)")->action(["SomeClass", "ParamMethod"]);
        $router->get()->name("test/optional/param/*(.*)")->action(["SomeClass", "OptionalMethod"]);
    }
}

class SomeClass
{
    public function SomeMethod() { return true; }
    public function ParamMethod($param) { return true; }
    public function OptionalMethod($param = "optional") { return true; }
}
