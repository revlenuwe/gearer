<?php


namespace Revlenuwe\Gearer;


use Illuminate\Support\ServiceProvider;

class GearerServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'gearer');

        $this->app->bind('gearer', function ($app){
            return new Gearer();
        });
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {

            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('gearer.php'),
            ], 'config');

        }
    }
}
