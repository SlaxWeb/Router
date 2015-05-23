<?php
namespace SlaxWeb\Router;

use SlaxWeb\Router\Exceptions as E;

class Request
{
    protected $_uri = "";
    protected $_domain = "";
    protected $_method = "";

    public function __get($param)
    {
        $property = "_{$param}";
        if (isset($this->{$property})) {
            return $this->{$property};
        }

        return null;
    }

    public function __toString()
    {
        return "{$this->_domain} ({$this->_method}) {$this->_uri}";
    }

    public function setUpCLI($uri)
    {
        $this->_uri = $uri;
        $this->_method = "CLI";
        $this->_domain = "Command Line";
    }

    public function setUpRequest($host, $method, $uri, $filename, $queryString)
    {
        if ($method === null) {
            throw new E\RequestException("REQUEST_METHOD not defined. Review your WebServer configuration", 500);
        }
        $this->_method = $method;

        if ($filename === null) {
            throw new E\RequestException("SCRIPT_FILENAME not defined. Review your WebServer configuration", 500);
        }
        if ($uri === null) {
            throw new E\RequestException("REQUEST_URI not defined. Review your WebServer configuration", 500);
        }
        $scriptName = str_replace(".", "\.", $filename);
        $this->_uri = preg_replace("~^/{$scriptName}~", "", $uri);
        if ($this->_uri !== "/") {
            $this->_uri = ltrim($this->_uri, "/");
        }

        if ($queryString !== "" && ($pos = strpos($this->_uri, $queryString)) !== false) {
            $this->_uri = substr($this->_uri, 0, $pos - 1);
        }

        if ($host === null) {
            throw new E\RequestException("HTTP_HOST not defined. Review your WebServer configuration", 500);
        }
        $this->_domain = $host;
    }
}
