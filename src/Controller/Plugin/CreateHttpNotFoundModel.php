<?php

declare (strict_types=1);
namespace Laminas\Mvc\Controller\Plugin;

use Laminas\Http\Response;
use Laminas\View\Model\View_Model;
class Create_Http_Not_Found_Model extends Abstract_Plugin
{
    /**
     * Create an HTTP view model representing a "not found" page
     *
     * @return ViewModel
     */
    public function __invoke(Response $response)
    {
        $response->set_status_code(404);
        return new View_Model(['content' => 'Page not found']);
    }
}