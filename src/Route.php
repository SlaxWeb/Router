<?php
/**
 * Route class of Router component
 *
 * The instance of a Route class is one route definition. Each Route must be
 * stored in the Container class.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router;

class Route
{
    use \SlaxWeb\GetSet\MagicGet;

    /**
     * URI
     *
     * @var string
     */
    protected $_uri = "";

    /**
     * Method
     *
     * @var string
     */
    protected $_method = "";

    /**
     * Action
     *
     * @var callable
     */
    protected $_callable = null;

    /**
     * Is default route
     *
     * @var bool
     */
    protected $_isDefault = false;

    /**
     * Method GET
     */
    const METHOD_GET = "GET";

    /**
     * Method POST
     */
    const METHOD_POST = "POST";

    /**
     * Method PUT
     */
    const METHOD_PUT = "PUT";

    /**
     * Method DELETE
     */
    const METHOD_DELETE = "DELETE";

    /**
     * Method Command Line Interface
     */
    const METHOD_CLI = "CLI";

    /**
     * Any Method
     */
    const METHOD_ANY = "ANY";

    /**
     * Set Route data
     *
     * Sets the retrieved data to internal properties. Prior to setting, the
     * method is checked, that it is valid, and raises an
     * 'InvalidMethodException' on error.
     *
     * @param string $uri Request URI regex without delimiter
     * @param string $method HTTP Request Method, accepts METHODO_* constant
     * @param callable $action Route action
     * @param bool $default Should the route be marked as default. Default bool(false)
     * @return self
     */
    public function set(string $uri, string $method, callable $action, bool $default = false): self
    {
        if (in_array(
            $method,
            [
                self::METHOD_GET,
                self::METHOD_POST,
                self::METHOD_PUT,
                self::METHOD_DELETE,
                self::METHOD_CLI,
                self::METHOD_ANY
            ]
        ) === false) {
            throw new Exception\InvalidMethodException(
                "Route HTTP Method '{$method}' is not valid."
            );
        }

        $this->_uri = preg_replace("~([^\\\\])\\$?\|\\^?~", "$1$|^", "~^{$uri}$~");
        $this->_method = $method;
        $this->_action = $action;
        $this->_isDefault = $default;

        return $this;
    }

    /**
     * Set 404 Route
     *
     * Sets the '404NoRouteFound' Route to its properties. This Route is used by
     * the Dispatcher if it can not find a matching Route for its Request.
     *
     * @param callable $action Route action
     * @return self
     */
    public function set404Route(callable $action): self
    {
        $this->_uri = "404RouteNotFound";
        $this->_method = self::METHOD_ANY;
        $this->_action = $action;

        return $this;
    }
}
