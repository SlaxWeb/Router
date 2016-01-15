<?php
namespace SlaxWeb\Router;

/**
 * Router response component
 *
 * Gathers all output when initialized, and starts output buffering.
 * When the class is destructed, it outputs the buffer.
 *
 * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @package \SlaxWeb\Libraries
 * @version v0.3
 * @license MIT
 *
 * @copyright (c) 2015 SlaxWeb
 */
class Response
{
    /**
     * Response body
     *
     * @var string
     */
    protected $_body = "";

    /**
     * Response HTTP Status code
     *
     * @var int
     */
    protected $_status = 200;

    /**
     * Logger
     *
     * @var Psr\Log\LoggerInterface
     */
    protected $_logger = null;

    /*
     * Header constants
     */
    const H_CONTENT_TYPE = "Content-Type";
    const H_LOCATION = "Location";

    /**
     * Class constructor
     *
     * Starts the output buffering, and sets the logger if it was supplied.
     *
     * @param \Psr\Log\LoggerInterface $logger Logger object that implements the LoggerInterface
     * @return void
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function __construct(\Psr\Log\LoggerInterface $logger = null)
    {
        ob_start();
        if ($logger !== null) {
            $this->_logger = $logger;
            $this->_log("info", "Response class initialized");
        }
    }

    /**
     * Class destructor
     *
     * Sets response code and flushes the output buffer.
     *
     * @return void
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function __destruct()
    {
        $this->_log(
            "info",
            "Class is destructing. Set response code ({$this->_status}), output the body, and flush buffers."
        );
        $this->_log("debug", "Body output", ["output" => $this->_body]);
        http_response_code($this->_status);
        echo $this->_body;
        ob_end_flush();
        ob_flush();
        flush();
    }

    /**
     * Magic set
     *
     * Set a protected class property. Append an underscore to the name,
     * and try to set the passed in value to that property. If the
     * property does not exist, an warning is triggered.
     *
     * @param string $param Name of the property
     * @param mixed $value Value of the property
     * @return void
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function __set($param, $value)
    {
        $prop = "_{$param}";
        if (isset($this->{$prop}) === false) {
            $msg = "Property {$param} does not exist in " . __CLASS__
                . ". Can not set value";
            $this->_log("error", $msg);
            throw new \SlaxWeb\Router\Exceptions\UnknownPropertyException(
                500,
                $msg
            );
        }

        $this->{$prop} = $value;
    }

    /**
     * Magic get
     *
     * Get a protected class property value. Append an underscore to the
     * name, and try to retrieve the value of the property and return
     * it. If the property does not exist, a warning is triggered.
     *
     * @param string $param Name of the property
     * @return mixed Value of the protected property
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function __get($param)
    {
        $prop = "_{$param}";
        if (isset($this->{$prop}) === false) {
            $msg = "Property {$param} does not exist in " . __CLASS__
                . ". Can not get value";
            $this->_log("error", $msg);
            throw new \SlaxWeb\Router\Exceptions\UnknownPropertyException(
                500,
                $msg
            );
        }

        return $this->{$prop};
    }

    /**
     * Add to output
     *
     * Add output to the Response component. All output is sent to the browser
     * after the class gets destroyed. If input is not a string, an exception is
     * thrown.
     *
     * @param string $output Output that is added to the buffer
     * @return void
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function addOutput($output)
    {
        if (is_string($output) === false) {
            $msg = "\$output is not string";
            $this->_log("error", $msg, ["output" => $output]);
            throw new \SlaxWeb\Router\Exceptions\NotStringException(
                500,
                $msg
            );
        }

        $this->_body .= $output;
    }

    /**
     * Set response header
     *
     * Sets a header to the received value.
     *
     * @param string $name Name of the header, suggested are the class H_
     *                     constants
     * @param string $value Value of the header to be set
     * @return bool Returns true on success, false on error
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function setHeader($name, $value)
    {
        if (is_string($name) === false || is_string($value) === false) {
            $this->_log(
                "error",
                "Can not set header. \$name and/or \$value are not strings.",
                ["name" => $name, "value" => $value]
            );
            return false;
        }

        header("{$name}: {$value}");
        return true;
    }

    /**
     * Add a redirect
     *
     * Adds a redirect to the output component. Be aware, that this method
     * does not prevent further execution! You should take care of that youself.
     *
     * @param string $location Location of the redirect, can be a full URL, an
     *                         absolute, or relative path.
     * @param int $status HTTP Status code, default: 303
     * @return bool Returns true on success, false on error
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    public function redirect($location, $status = 303)
    {
        if ($this->setHeader(self::H_LOCATION, $location) === true) {
            $this->status = intval($status);
            return true;
        }
        return false;
    }

    /**
     * Write to log
     *
     * Write message to log in the defined level, and with passed in log data.
     *
     * @param string $level Level that complies with the PSR4 standard
     * @param string $message Log message
     * @param array $logData Additional data to be logged, default: []
     * @return void
     * @author Tomaz Lovrec <tomaz.lovrec@gmail.com>
     */
    protected function _log($level, $message, $logData = [])
    {
        if ($this->_logger === null) {
            return;
        }

        $method = "add" . strtoupper($level);
        $this->_logger->{$method}($message, $logData);
    }
}
