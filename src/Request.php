<?php
namespace SlaxWeb\Router;

/**
 * Request class
 *
 * The Request class extends the Symfony\Component\HttpFoundation\Request class
 * and provides an additional 'addQuery' method for adding parameters to the
 * query parameters
 *
 * @package   SlaxWeb\Router
 * @author    Tomaz Lovrec <tomaz.lovrec@gmail.com>
 * @copyright 2016 (c) Tomaz Lovrec
 * @license   MIT <https://opensource.org/licenses/MIT>
 * @link      https://github.com/slaxweb/
 * @version   0.4
 */
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
