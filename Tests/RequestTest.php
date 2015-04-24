<?php
class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testSetup()
    {
        $filename = "test.php";
        $method = "POST";
        $host = "www.test.com";
        $uri = "/test/uri";
        $req = new \SlaxWeb\Router\Request();

        $req->setUpRequest($host, $method, $uri, $filename);

        $this->assertEquals("/test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
    }

    public function testScriptNameRemoved()
    {
        $filename = "test.php";
        $method = "POST";
        $host = "www.test.com";
        $uri = "/test.php/test/uri";
        $req = new \SlaxWeb\Router\Request();

        $req->setUpRequest($host, $method, $uri, $filename);

        $this->assertEquals("/test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
    }

    public function testPortRetained()
    {
        $filename = "test.php";
        $method = "POST";
        $host = "www.test.com:8080";
        $uri = "/test/uri";
        $req = new \SlaxWeb\Router\Request();

        $req->setUpRequest($host, $method, $uri, $filename);

        $this->assertEquals("/test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com:8080", $req->domain);
    }

    public function  testCliSetup()
    {
        $req = new \SlaxWeb\Router\Request();
        $req->setUpCLI("test/uri");

        $this->assertEquals("test/uri", $req->uri);
        $this->assertEquals("CLI", $req->method);
        $this->assertEquals("Command Line", $req->domain);
    }
}
