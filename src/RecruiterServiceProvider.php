<?php

namespace Jeylabs\Recruiter;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Container\Container as Application;

class RecruiterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $source = __DIR__ . '/config/recruiter.php';
        $this->publishes([$source => config_path('recruiter.php')]);
        $this->mergeConfigFrom($source, 'recruiter');
    }

    public function register()
    {
        $this->registerBindings($this->app);
    }

    protected function registerBindings(Application $app)
    {
        $app->singleton('recruiter', function ($app) {
            $config = $app['config'];
            return new Recruiter(
                $config->get('recruiter.secret_key', null),
                $config->get('recruiter.recruiter_api_babe_uri', null),
                $config->get('recruiter.async_requests', false)
            );
        });
        $app->alias('recruiter', Recruiter::class);

    }
}
