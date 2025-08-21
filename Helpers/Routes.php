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
     * @var SQL Database.
     */
    private SQL $db;

    /**
     * Registers the routes.
     * @return void
     */
    public function register(): void
    {
        $this->router = new Router();

        $this->db = new SQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        $uq = $this->db->query('SELECT `username` FROM `users`');
        if ($uq->num_rows != 0) { $this->users = $uq->fetch_all(MYSQLI_ASSOC); }

        $cq = $this->db->query('SELECT `slug` FROM `collections` WHERE `visible` = 1');
        if ($cq->num_rows != 0) { $this->collections = $cq->fetch_all(MYSQLI_ASSOC); }

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

        foreach ($this->users as $user) {
            $rq = $this->db->query("SELECT `slug` FROM `recipes` WHERE `uuid` = '".new Users()->usernameToUuid($user['username'])."'");
            if ($rq->num_rows != 0) { $recipes = $rq->fetch_all(MYSQLI_ASSOC); }
            foreach ($recipes as $recipe) {
                $this->router->GET('/recipes/' . $user['username'] . '/' . $recipe['slug'], '/api/recipes/[user]/[slug]/get.php');
                $this->router->GET('/recipes/' . $user['username'] . '/' . $recipe['slug'] . '/reviews', '/api/recipes/[user]/[slug]/reviews/get.php');
            }
        }
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

    /**
     * /meal-plans routes
     * @return void
     */
    private function mealplans(): void
    {
        $this->router->GET('/meal-plans', '/api/meal-plans/get.php');

        $this->router->POST('/meal-plans/item', '/api/meal-plans/item/post.php');
        $this->router->DELETE('/meal-plans/item', '/api/meal-plans/item/delete.php');

        //$this->router->GET('/meal-plans/share', '/api/meal-plans/share/get.php');

        //$this->router->GET('/meal-plans/requests', '/api/meal-plans/requests/get.php');
    }
}
