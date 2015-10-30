<?php
class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testSetup()
    {
        $method = "POST";
        $host = "www.test.com";
        $uri = "/test/uri";
        $scriptName = "/test.php";
        $req = new \SlaxWeb\Router\Request();

        $req->setBaseRequest("http", $host, $method);
        $req->parseRequestUri($uri, $scriptName);

        $this->assertEquals("", $req->dir);
        $this->assertEquals("test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
        $this->assertEquals("http", $req->protocol);
    }

    public function testEmptyUri()
    {
        $method = "POST";
        $host = "www.test.com";
        $uri = "/";
        $scriptName = "/test.php";
        $req = new \SlaxWeb\Router\Request();

        $req->setBaseRequest("http", $host, $method);
        $req->parseRequestUri($uri, $scriptName);

        $this->assertEquals("", $req->dir);
        $this->assertEquals("/", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
        $this->assertEquals("http", $req->protocol);
    }

    public function testScriptNameRemoved()
    {
        $method = "POST";
        $host = "www.test.com";
        $uri = "/test.php/test/uri";
        $scriptName = "/test.php";
        $req = new \SlaxWeb\Router\Request();

        $req->setBaseRequest("http", $host, $method);
        $req->parseRequestUri($uri, $scriptName);

        $this->assertEquals("test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
    }

    public function testPortRetained()
    {
        $method = "POST";
        $host = "www.test.com:8080";
        $uri = "/test/uri";
        $scriptName = "/test.php";
        $req = new \SlaxWeb\Router\Request();

        $req->setBaseRequest("http", $host, $method);
        $req->parseRequestUri($uri, $scriptName);

        $this->assertEquals("test/uri", $req->uri);
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

    public function testQueryString()
    {
        $method = "POST";
        $host = "www.test.com";
        $uri = "/test/uri?test=test";
        $scriptName = "/test.php";
        $req = new \SlaxWeb\Router\Request();

        $req->setBaseRequest("http", $host, $method);
        $req->parseRequestUri($uri, $scriptName);

        $this->assertEquals("test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
        $this->assertArrayHasKey("test", $_GET);
        $this->assertEquals("test", $_GET["test"]);
    }

    public function testSubDir()
    {
        $method = "POST";
        $host = "www.test.com";
        $uri = "/test/subdir/test/uri";
        $scriptName = "/test/subdir/test.php";
        $req = new \SlaxWeb\Router\Request();

        $req->setBaseRequest("http", $host, $method);
        $req->parseRequestUri($uri, $scriptName);

        $this->assertEquals("/test/subdir", $req->dir);
        $this->assertEquals("test/uri", $req->uri);
        $this->assertEquals("POST", $req->method);
        $this->assertEquals("www.test.com", $req->domain);
    }
}
