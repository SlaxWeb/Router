<?php
namespace SlaxWeb\Router;

use SlaxWeb\Router\Exceptions as E;

class Request
{
    protected $_dir = "";
    protected $_uri = "";
    protected $_domain = "";
    protected $_method = "";
    protected $_protocol = "";

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
        $this->_protocol = "cli";
    }

    public function setBaseRequest($protocol, $host, $method)
    {
        if ($method === null) {
            throw new E\RequestException("REQUEST_METHOD not defined. Review your WebServer configuration", 500);
        }
        if ($host === null) {
            throw new E\RequestException("HTTP_HOST not defined. Review your WebServer configuration", 500);
        }

        $this->_protocol = $protocol;
        $this->_domain = $host;
        $this->_method = $method;
    }

    public function parseRequestUri($requestUri, $scriptName)
    {
        $requestUri = parse_url("{$this->_protocol}://{$this->domain}{$requestUri}");
        $queryString = isset($requestUri["query"]) ? $requestUri["query"] : "";
        $requestUri = isset($requestUri["path"]) ? $requestUri["path"] : "";

        if (strpos($requestUri, $scriptName) === 0) {
            $requestUri = substr($requestUri, strlen($scriptName));
        } elseif (strpos($requestUri, dirname($scriptName)) === 0) {
            $requestUri = substr($requestUri, strlen(dirname($scriptName)));
        }

        /*
         * ensure that a correct URI is found on servers that require it
         * in the query string, and fix the QUERY_STRING server var and
         * $_GET array
         */
        if (trim($requestUri, "/") === "" && strncmp($queryString, "/", 1) === 0) {
            $queryString = explode("?", $queryString, 2);
            $requestUri = $queryString[0];
            $_SERVER["QUERY_STRING"] = isset($queryString[1]) ? $queryString[1] : "";
        } else {
            $_SERVER["QUERY_STRING"] = $queryString;
        }

        parse_str($_SERVER["QUERY_STRING"], $_GET);

        $this->_uri = $requestUri !== "/" && $requestUri !== ""
            ? $this->_sanitizeUri($requestUri)
            : "/";

        // parse the directory
        $this->_dir = dirname($scriptName);
        // if there is no subdir, just set the property to an empty string
        if ($this->_dir === "/") {
            $this->_dir = "";
        }
    }

    protected function _sanitizeUri($uri)
    {
        $uriParts = [];
        $tok = strtok($uri, "/");
        while ($tok !== false) {
            if ((empty($tok) === false || $tok === "0") && $tok !== "..") {
                $uriParts[] = $tok;
            }
            $tok = strtok("/");
        }

        return implode("/", $uriParts);
    }
}
