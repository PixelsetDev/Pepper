<?php

namespace OurCookbook;

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
     * Registers the routes.
     * @return void
     */
    public function register(): void
    {
        $this->router = new Router();

        $this->chefs();
    }

    /**
     * /chefs routes
     * @return void
     */
    private function chefs(): void
    {
        $this->router->GET('/chefs','/api/chefs/get.php');
    }
}
