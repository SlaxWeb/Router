<?php
/**
 * Request class Tests
 *
 * The Request class must extend the Symfony\Component\HttpFoundation\Request
 * class and provide an additional 'addQuery' method for adding parameters to
 * the query parameters. This test ensures that this method functions properly.
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.3
 */
namespace SlaxWeb\Router;

class Request extends \Symfony\Component\HttpFoundation\Request
{
    /**
     * Add query parameters
     *
     * Add the retrieved array to the query parameters.
     *
     * @param array $params Parameters to be added to the query parameters
     * @return void
     */
    public function addQuery(array $params)
    {
        $this->query->add($params);
    }
}
