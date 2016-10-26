<?php
declare(strict_types = 1);
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

use Symfony\Component\HttpFoundation\RedirectResponse;

class Response extends \Symfony\Component\HttpFoundation\Response
{
    /**
     * Redirect response
     *
     * @var \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected $redirect = null;

    /**
     * Add To Content
     *
     * Add retrieved input to the end of already existing content in the
     * Response object.
     *
     * @param string $content Content to be added.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addContent(string $content): \Symfony\Component\HttpFoundation\Response
    {
        return $this->setContent($this->getContent() . $content);
    }

    /**
     * Redirect
     *
     * Creates a new Redirect Response object and stores it to the class property.
     * If a direct write is requested through the second optional parameter, the
     * send method is immediatelly called, which is the default behaviour. To avoid
     * that, bool(false) has to be sent as the second parameter.
     *
     * If the first parameter is not a full URL, then it is treated as an URI and
     * will be appended to the request host.
     *
     * @param string $url URL/URI to redirect to
     * @param bool $write Write response and stop further execution
     * @return void
     */
    public function redirect(string $url, bool $write = true)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            $url = $this->getUriForPath($url);
        }

        $this->redirect = RedirectResponse::create($url);
        if ($write) {
            $this->send();
            exit;
        }
    }

    /**
     * Send
     *
     * Override the 'send' method to check if a redirect response has been set,
     * and send that Response instead. If not, a normal call to the parent 'send'
     * method is made.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function send(): \Symfony\Component\HttpFoundation\Response
    {
        if ($this->redirect !== null) {
            return $this->redirect->send();
        }
        return parent::send();
    }
}
