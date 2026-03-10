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
        if (str_contains($_SERVER['REQUEST_URI'], '/v1/collections')) { $this->collections(); }
        if (str_contains($_SERVER['REQUEST_URI'], '/v1/feed')) { $this->feed(); }
        if (str_contains($_SERVER['REQUEST_URI'], '/v1/shopping-lists')) { $this->shoppingLists(); }
        if (str_contains($_SERVER['REQUEST_URI'], '/v1/meal-plans')) { $this->mealPlans(); }
        $this->other();
    }

    /**
     * /user routes
     * @return void
     */
    private function user(): void
    {
        $this->router->GET('/v1/users','/api/users/get.php');
        $this->router->GET('/v1/users/[me]','/api/users/me/get.php');
        $this->router->PUT('/v1/users/[me]','/api/users/me/update.php');
        $this->router->GET('/v1/users/[me]/reviews','/api/users/me/reviews/get.php');

        $uq = $this->db->fetchAll('SELECT `uuid`, `username` FROM users');
        if ($this->db->numRows() != 0) {
            foreach ($uq as $user) {
                $this->router->GET('/v1/users/' . $user['uuid'], '/api/users/[id]/get.php');
                $this->router->GET('/v1/users/' . $user['username'], '/api/users/[id]/get.php');
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
        $this->router->POST('/v1/recipes', '/api/recipes/create.php');

        $recipes = $this->db->fetchAll("SELECT `id`,`slug`,`author` FROM recipes WHERE 1");
        if ($this->db->numRows() != 0) {
            $uh = new Users();
            foreach ($recipes as $recipe) {
                $this->router->GET('/v1/recipes/' . $recipe['id'], '/api/recipes/[id]/get.php');
                $this->router->PUT('/v1/recipes/' . $recipe['id'], '/api/recipes/[id]/update.php');
                $this->router->DELETE('/v1/recipes/' . $recipe['id'], '/api/recipes/[id]/delete.php');

                $this->router->GET('/v1/recipes/' . $recipe['id'] . '/steps', '/api/recipes/[id]/steps/get.php');
                $this->router->POST('/v1/recipes/' . $recipe['id'] . '/steps', '/api/recipes/[id]/steps/create.php');
                $this->router->PUT('/v1/recipes/' . $recipe['id'] . '/steps', '/api/recipes/[id]/steps/update.php');
                $this->router->DELETE('/v1/recipes/' . $recipe['id'] . '/steps', '/api/recipes/[id]/steps/delete.php');

                $this->router->GET('/v1/recipes/' . $recipe['id'] . '/reviews', '/api/recipes/[id]/reviews/get.php');
                $this->router->POST('/v1/recipes/' . $recipe['id'] . '/reviews', '/api/recipes/[id]/reviews/create.php');
                $this->router->PUT('/v1/recipes/' . $recipe['id'] . '/reviews', '/api/recipes/[id]/reviews/update.php');
                $this->router->DELETE('/v1/recipes/' . $recipe['id'] . '/reviews', '/api/recipes/[id]/reviews/delete.php');

                $this->router->GET('/v1/recipes/' . $recipe['id'] . '/ingredients', '/api/recipes/[id]/ingredients/get.php');
                $this->router->POST('/v1/recipes/' . $recipe['id'] . '/ingredients', '/api/recipes/[id]/ingredients/create.php');
                $this->router->PUT('/v1/recipes/' . $recipe['id'] . '/ingredients', '/api/recipes/[id]/ingredients/update.php');
                $this->router->DELETE('/v1/recipes/' . $recipe['id'] . '/ingredients', '/api/recipes/[id]/ingredients/delete.php');

                $this->router->GET('/v1/recipes/' . $uh->uuidToUsername($recipe['author']) . '/' . $recipe['slug'], '/api/recipes/[id]/get.php');
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
        $this->router->POST('/v1/ingredients','/api/ingredient/create.php');

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
     * /collections routes
     * @return void
     */
    private function collections(): void
    {
        $this->router->GET('/v1/collections','/api/collections/get.php');
        $this->router->POST('/v1/collections','/api/collections/create.php');

        $collections = $this->db->fetchAll('SELECT `id`,`author`,`slug` FROM collections');
        if ($this->db->numRows() != 0) {
            foreach ($collections as $collection) {
                $this->router->GET('/v1/collections/' . $collection['id'], '/api/collections/[id]/get.php');
                $this->router->DELETE('/v1/collections/' . $collection['id'], '/api/collections/[id]/delete.php');
                $this->router->PUT('/v1/collections/' . $collection['id'], '/api/collections/[id]/update.php');

                $this->router->GET('/v1/collections/' . $collection['slug'], '/api/collections/[id]/get.php');
            }
        }
    }

    /**
     * /feed routes
     * @return void
     */
    private function feed(): void
    {
        $this->router->GET('/v1/feed','/api/feed/get.php');
    }

    /**
     * /shopping-lists routes
     * @return void
     */
    private function shoppingLists(): void
    {
        $this->router->GET('/v1/shopping-lists','/api/shopping-lists/get.php');
        $this->router->POST('/v1/shopping-lists','/api/shopping-lists/create.php');

        $lists = $this->db->fetchAll("SELECT `uuid` FROM shopping_lists WHERE 1");
        if ($this->db->numRows() != 0) {
            foreach ($lists as $list) {
                $this->router->GET('/v1/shopping-lists/'.$list['uuid'],'/api/shopping-lists/[id]/get.php');
                $this->router->PUT('/v1/shopping-lists/'.$list['uuid'],'/api/shopping-lists/[id]/update.php');
                $this->router->DELETE('/v1/shopping-lists/'.$list['uuid'],'/api/shopping-lists/[id]/delete.php');
                $this->router->POST('/v1/shopping-lists/'.$list['uuid'].'/items','/api/shopping-lists/[id]/items/create.php');
            }

            // Delayed this so doesn't slow down processing of above for newer lists.
            foreach ($lists as $list) {
                $items = $this->db->fetchAll("SELECT `id` FROM shopping_lists_items WHERE list_uuid = ?", [$list['uuid']]);
                if ($this->db->numRows() != 0) {
                    foreach ($items as $item) {
                        $this->router->PUT('/v1/shopping-lists/'.$list['uuid'].'/items/'.$item['id'],'/api/shopping-lists/[id]/items/update.php');
                        $this->router->DELETE('/v1/shopping-lists'.$list['uuid'].'/items/'.$item['id'],'/api/shopping-lists/[id]/items/delete.php');
                    }
                }
            }
        }
    }

    /**
     * /meal-plans routes
     * @return void
     */
    private function mealPlans(): void
    {
        $this->router->GET('/v1/meal-plans','/api/meal-plans/get.php');
        $this->router->POST('/v1/meal-plans','/api/meal-plans/create.php');
        $this->router->POST('/v1/meal-plans/items','/api/meal-plans/items/create.php');

        $plans = $this->db->fetchAll("SELECT `author` FROM meal_plans WHERE 1");
        if ($this->db->numRows() != 0) {
            foreach ($plans as $plan) {
                $this->router->GET('/v1/meal-plans/'.$plan['author'],'/api/meal-plans/[id]/get.php');
                $this->router->PUT('/v1/meal-plans/'.$plan['author'],'/api/meal-plans/[id]/update.php');
                $this->router->DELETE('/v1/meal-plans/'.$plan['author'],'/api/meal-plans/[id]/delete.php');
            }
        }

        $items = $this->db->fetchAll("SELECT `id` FROM meal_plans_items WHERE plan_id = ?", [$plan['author']]);
        if ($this->db->numRows() != 0) {
            foreach ($items as $item) {
                $this->router->DELETE('/v1/meal-plans/items/'.$item['id'],'/api/meal-plans/items/[item]/delete.php');
            }
        }
    }

    /**
     * Other routes
     * @return void
     */
    private function other(): void
    {
        $this->router->GET('/v1/statistics','/api/statistics/get.php');
        $this->router->GET('/v1/moderate/health-check','/api/moderate/health-check/get.php');
    }
}
