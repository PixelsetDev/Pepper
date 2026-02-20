<?php

namespace Pepper\Processes;

use Starlight\Database\MySQL;
use Starlight\HTTP\Router;
use starlight\HTTP\Types\ResponseCode;

/**
 * Handles the routing for the API
 */
class Routes {
    /**
     * @var Router Starlight router.
     */
    private Router $router;

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

        if (!str_contains($_SERVER['REQUEST_URI'], '/v1')) {
            return;
        }

        if (str_contains($_SERVER['REQUEST_URI'], '/v1/users')) { $this->user(); }
        if (str_contains($_SERVER['REQUEST_URI'], '/v1/recipes')) { $this->recipe(); }
        if (str_contains($_SERVER['REQUEST_URI'], '/v1/ingredients')) { $this->ingredient(); }
        $this->other();
    }

    /**
     * /user routes
     * @return void
     */
    private function user(): void
    {
        $this->router->GET('/v1/users','/api/user/get.php');
        $this->router->GET('/v1/users/[me]','/api/user/me/get.php');

        $uq = $this->db->fetchAll('SELECT `uuid`, `username` FROM users');
        if ($this->db->numRows() != 0) {
            foreach ($uq as $user) {
                $this->router->GET('/v1/users/' . $user['uuid'], '/api/user/[id]/get.php');
                $this->router->GET('/v1/users/' . $user['username'], '/api/user/[id]/get.php');
            }
        }
    }

    /**
     * /recipe routes
     * @return void
     */
    private function recipe(): void
    {
        $this->router->GET('/v1/recipes', '/api/recipes/get.php');

        $recipes = $this->db->fetchAll("SELECT `id`,`slug`,`author_uuid` FROM recipes WHERE 1");
        if ($this->db->numRows() != 0) {
            $uh = new Users();
            foreach ($recipes as $recipe) {
                $this->router->GET('/v1/recipes/' . $recipe['id'], '/api/recipes/[id]/get.php');
                $this->router->GET('/v1/recipes/' . $recipe['id'] . '/steps', '/api/recipes/[id]/steps/get.php');
                $this->router->GET('/v1/recipes/' . $recipe['id'] . '/reviews', '/api/recipes/[id]/reviews/get.php');
                $this->router->GET('/v1/recipes/' . $recipe['id'] . '/ingredients', '/api/recipes/[id]/ingredients/get.php');
                $this->router->GET('/v1/recipes/' . $uh->uuidToUsername($recipe['author_uuid']) . '/' . $recipe['slug'], '/api/recipes/[id]/get.php');
            }
        }

        $this->router->GET('/v1/recipes/categories','/api/recipes/categories/get.php');

        $this->router->GET('/v1/recipes/categories/0','/api/recipes/categories/[id]/get.php');
        $categories = $this->db->fetchAll('SELECT `id` FROM recipes_categories');
        foreach ($categories as $category) {
            $this->router->GET('/v1/recipes/categories/'.$category['id'],'/api/recipes/categories/[id]/get.php');
        }
    }

    /**
     * /ingredient routes
     * @return void
     */
    private function ingredient(): void
    {
        $this->router->GET('/v1/ingredients','/api/ingredient/get.php');

        $this->router->GET('/v1/ingredients/dietary','/api/ingredient/dietary/get.php');

        $this->router->GET('/v1/ingredients/categories','/api/ingredient/categories/get.php');

        $this->router->GET('/v1/ingredients/categories/0','/api/ingredient/categories/[id]/get.php');
        $categories = $this->db->fetchAll('SELECT `id` FROM ingredients_categories');
        foreach ($categories as $category) {
            $this->router->GET('/v1/ingredients/categories/'.$category['id'],'/api/ingredient/categories/[id]/get.php');
        }

        $ingredients = $this->db->fetchAll('SELECT `id` FROM ingredients');
        foreach ($ingredients as $ingredient) {
            $this->router->GET('/v1/ingredients/'.$ingredient['id'],'/api/ingredient/[id]/get.php');
        }
    }

    /**
     * Other routes
     * @return void
     */
    private function other(): void
    {
        $this->router->GET('/v1/statistics','/api/statistics/get.php');
    }
}
