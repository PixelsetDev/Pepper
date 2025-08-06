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
     * @var array List of collections
     */
    private array $collections;

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

        $cq = $db->query('SELECT `slug` FROM `collections` WHERE `visible` = 1');
        if ($cq->num_rows != 0) { $this->collections = $cq->fetch_all(MYSQLI_ASSOC); }

        $this->users();
        $this->recipes();
        $this->collections();
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

    /**
     * /collections routes
     * @return void
     */
    private function collections(): void
    {
        $this->router->GET('/collections', '/api/collections/get.php');

        foreach ($this->collections as $collection) {
            $this->router->GET('/collections/'.$collection['slug'], '/api/collections/[id]/get.php');
        }
    }
}
