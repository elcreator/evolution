<?php

namespace EvolutionCMS\Providers;

use EvolutionCMS\Extensions\Router;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\RoutingServiceProvider as IlluminateRoutingServiceProvider;
use Illuminate\Support\Facades\Route;

class RoutingServiceProvider extends IlluminateRoutingServiceProvider
{
    /**
     * Register the service provider and routes
     */
    public function register()
    {
        parent::register();

        if ($this->app->isFrontend() || is_cli()) {
            if (is_readable(EVO_CORE_PATH . 'custom/routes.php')) {
                Route::middleware('web')->group(EVO_CORE_PATH . 'custom/routes.php');
            }

            Route::fallbackToParser();
        }
    }

    /**
     * We need to overload provider to register our router
     * to use custom route methods
     */
    protected function registerRouter()
    {
        $this->app->singleton('router', function ($app) {
            return new Router($app['events'], $app);
        });

        $this->app->alias('router', Registrar::class);
    }
}
