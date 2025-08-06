<?php

namespace Pepper\Helpers;

use Starlight\Database\SQL;
use Starlight\HTTP\Router;

/**
 * Handles the routing for the API
 */
class Routes {
    /**
     * @var Router Starlight router.
     */
    private Router $router;

    /**
     * @var array List of users
     */
    private array $users;

    /**
     * Registers the routes.
     * @return void
     */
    public function register(): void
    {
        $this->router = new Router();

        $db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $uq = $db->query('SELECT `username` FROM `users`');
        if ($uq->num_rows != 0) { $this->users = $uq->fetch_all(MYSQLI_ASSOC); }

        $this->users();
        $this->recipes();
    }

    /**
     * /users routes
     * @return void
     */
    private function users(): void
    {
        $this->router->GET('/users','/api/users/get.php');

        foreach ($this->users as $user) {
            $this->router->GET('/users/'.$user['username'], '/api/users/[id]/get.php');
        }
    }

    /**
     * /recipes routes
     * @return void
     */
    private function recipes(): void
    {
        $this->router->GET('/recipes', '/api/recipes/get.php');
    }
}
