<?php

declare(strict_types=1);

/**
 * Example: Creating an action controller with controller plugins.
 *
 * Action controllers route requests to action methods (indexAction, createAction, etc.)
 * and use controller plugins for common tasks like redirects and parameter access.
 *
 * This example shows a typical CRUD controller pattern.
 */

// Namespace as it would appear in a real module:
namespace Application\Controller;

use Laminas\Mvc\Controller\Abstract_Action_Controller;
use Laminas\Mvc\Mvc_Event;
use Laminas\View\Model\View_Model;

/**
 * A typical action controller for managing User resources.
 *
 * Route params are accessed via the `params()` plugin.
 * Redirects are performed via the `redirect()` plugin.
 * Views are returned as ViewModel instances.
 */
class User_Controller extends Abstract_Action_Controller
{
    /**
     * @param \Application\Service\User_Service $user_service Injected by factory
     */
    public function __construct(
        private readonly \Application\Service\User_Service $user_service
    ) {
    }

    /**
     * List all users — mapped to GET /users
     */
    public function index_action(): View_Model
    {
        $users = $this->user_service->find_all();

        return new View_Model(['users' => $users]);
    }

    /**
     * Show a single user — mapped to GET /users/:id
     */
    public function view_action(): View_Model
    {
        // $this->params() is a controller plugin
        $id   = (int) $this->params()->from_route('id', 0);
        $user = $this->user_service->find($id);

        if (!$user) {
            // Return a 404 view model
            return $this->create_http_not_found_model($this->get_response());
        }

        return new View_Model(['user' => $user]);
    }

    /**
     * Delete a user — mapped to DELETE /users/:id (or POST with _method override)
     */
    public function delete_action(): \Laminas\Http\Response
    {
        $id = (int) $this->params()->from_route('id', 0);
        $this->user_service->delete($id);

        // $this->redirect() is a controller plugin
        return $this->redirect()->to_route('users');
    }
}

// ---- Factory for the controller ----
//
// namespace Application\Controller\Service;
//
// use Application\Controller\User_Controller;
// use Application\Service\User_Service;
// use Laminas\Service_Manager\Factory\Factory_Interface;
// use Psr\Container\Container_Interface;
//
// class User_Controller_Factory implements Factory_Interface
// {
//     public function __invoke(Container_Interface $container, string $name, ?array $options = null): User_Controller
//     {
//         return new User_Controller($container->get(User_Service::class));
//     }
// }

echo "See class definition above for a complete action controller example." . PHP_EOL;
