<?php
namespace SlaxWeb\Router;

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
 * @version   0.4
 *
 * @property string $uri
 * @property int $method
 * @property callable $action
 * @property bool $isDefault
 * @property bool $is404
 * @property string $beforeDispatch
 * @property string $afterDispatch
 */
class Route
{
    use \SlaxWeb\GetSet\MagicGet;

    /**
     * URI
     *
     * @var string
     */
    protected $uri = "";

    /**
     * Method
     *
     * @var int
     */
    protected $method = 0b0;

    /**
     * Action
     *
     * @var callable
     */
    protected $action = null;

    /**
     * Is default route
     *
     * @var bool
     */
    protected $isDefault = false;

    /**
     * Is 404 route
     *
     * @var bool
     */
    protected $is404 = false;

    /**
     * Pre dispatch hook
     *
     * @var string
     */
    protected $beforeDispatch = "";

    /**
     * Post dispatch hook
     *
     * @var string
     */
    protected $afterDispatch = "";

    /**
     * Magic Getter Property Prepend
     *
     * @var string
     */
    protected $_getSetPrepend = "";

    /**
     * Method GET
     */
    const METHOD_GET = 0b1;

    /**
     * Method POST
     */
    const METHOD_POST = 0b10;

    /**
     * Method PUT
     */
    const METHOD_PUT = 0b100;

    /**
     * Method DELETE
     */
    const METHOD_DELETE = 0b1000;

    /**
     * Method Command Line Interface
     */
    const METHOD_CLI = 0b10000;

    /**
     * Any Method
     */
    const METHOD_ANY = 0b11111;

    /**
     * Set Route data
     *
     * Sets the retrieved data to internal properties. Prior to setting, the
     * method is checked, that it is valid, and raises an
     * 'InvalidMethodException' on error.
     *
     * @param string $uri Request URI regex without delimiter
     * @param int $method HTTP Request Method, accepts METHODO_* constant
     * @param callable $action Route action
     * @param bool $default Should the route be marked as default. Default bool(false)
     * @return self
     */
    public function set(string $uri, int $method, callable $action, bool $default = false): self
    {
        if ((self::METHOD_ANY & $method) !== $method) {
            throw new Exception\InvalidMethodException(
                "Route does not contain a valid HTTP Method."
            );
        }

        $this->uri = preg_replace("~([^\\\\])\\$?\|\\^?~", "$1$|^", "~^{$uri}$~");
        $this->method = $method;
        $this->action = $action;
        $this->isDefault = $default;

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
        $this->uri = "404RouteNotFound";
        $this->method = self::METHOD_ANY;
        $this->action = $action;
        $this->is404 = true;

        return $this;
    }

    /**
     * Set hook
     *
     * Sets a hook name for the before or after dispatch hook name to be executed
     * before or after the Route has been dispatched. Takes the name of the hook
     * as the first argument, and a bool value as the second. If ommited or set
     * to false, the hook name will be used before dispatch event, and after dispatch
     * event if set to true.
     *
     * @param string $hook Name of the hook to be executed
     * @param bool $after Defines if the hook is to be executed before or after dispatch, default bool(false)
     * @return self
     */
    public function setHook(string $hook, bool $after = false): self
    {
        if ($after) {
            $this->afterDispatch = $hook;
            return $this;
        }
        $this->beforeDispatch = $hook;
        return $this;
    }
}
