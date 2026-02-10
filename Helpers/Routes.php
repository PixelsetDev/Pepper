<?php

namespace Pepper\Helpers;

use Starlight\Database\MySQL;
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
     * @var MySQL Database.
     */
    private MySQL $db;

    /**
     * Registers the routes.
     * @return void
     */
    public function register(): void
    {
        $this->router = new Router();

        $this->db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        $uq = $this->db->fetchAll('SELECT `username` FROM `users`');
        if ($this->db->numRows() != 0) { $this->users = $uq; }

        $cq = $this->db->fetchAll('SELECT `slug` FROM `collections` WHERE `visible` = 1');
        if ($this->db->numRows() != 0) { $this->collections = $cq; }

        $this->users();
        $this->recipes();
        $this->collections();
        $this->mealplans();
    }

    /**
     * /users routes
     * @return void
     */
    private function users(): void
    {
        $this->router->GET('/user','/api/user/get.php');

        foreach ($this->users as $user) {
            $this->router->GET('/user/'.$user['username'], '/api/user/[id]/get.php');
        }
    }

    /**
     * /recipes routes
     * @return void
     */
    private function recipes(): void
    {
        $this->router->GET('/recipes', '/api/recipes/get.php');

        foreach ($this->users as $user) {
            $recipes = $this->db->fetchAll("SELECT `slug` FROM `recipes` WHERE `uuid` = '".new Users()->usernameToUuid($user['username'])."'");
            if ($this->db->numRows() != 0) {
                foreach ($recipes as $recipe) {
                    $this->router->GET('/recipe/' . $user['username'] . '/' . $recipe['slug'], '/api/recipes/[user]/[slug]/get.php');
                    $this->router->GET('/recipe/' . $user['username'] . '/' . $recipe['slug'] . '/reviews', '/api/recipes/[user]/[slug]/reviews/get.php');
                }
            }
        }
    }
}
