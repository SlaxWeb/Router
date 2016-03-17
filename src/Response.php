<?php
/**
 * Response class
 *
 * The Response class extends the Symfony\Component\HttpFoundation\Response
 * class and provides an additional 'addContent' method for concatenating
 * content with existing content.
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
     * Add To Content
     *
     * Add retrieved input to the end of already existing content in the
     * Response object.
     *
     * @param string $content Content to be added.
     * @return self
     */
    public function addContent($content):self
    {
        return $this->setContent($this->getContent() . $content);
    }
}
