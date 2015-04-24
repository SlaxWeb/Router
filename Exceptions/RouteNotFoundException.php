<?php
namespace SlaxWeb\Router\Exceptions;

use \SlaxWeb\Router\Request;

class RouteNotFoundException extends \Exception
{
    protected $_request = [];

    public function __construct($message = "", $code = 0, Request $request = null, \Exception $previous = null)
    {
        $this->_request = $request;
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\nREQUEST: {$this->_request}\n";
    }
}
