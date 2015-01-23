<?php
namespace SlaxWeb\Router\Exceptions;

class RouteNotFoundException extends \Exception
{
    protected $_request = [];

    public function __construct($message = "", $code = 0, array $request = [], \Exception $previous = null)
    {
        $this->_request = $request;
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\nREQUEST: " . var_export($this->_request, true) . "\n";
    }
}
