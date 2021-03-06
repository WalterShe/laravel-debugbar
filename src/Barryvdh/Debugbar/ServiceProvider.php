<?php namespace Barryvdh\Debugbar;


class ServiceProvider extends \Illuminate\Support\ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('barryvdh/laravel-debugbar');
        if($this->app['config']->get('laravel-debugbar::config.enabled')){
            /** @var LaravelDebugbar $debugbar */
            $debugbar = $this->app['debugbar'];
            $debugbar->boot();
        }

        $this->commands('command.debugbar.publish');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $self = $this;
        $app = $this->app;
        $this->app['debugbar'] = $this->app->share(function ($app) use($self) {
                $debugbar = new LaravelDebugBar($app);

                $sessionManager = $app['session'];
                $httpDriver = new SymfonyHttpDriver($sessionManager);
                $debugbar->setHttpDriver($httpDriver);

                return $debugbar;
            });

        $this->app['command.debugbar.publish'] = $this->app->share(function($app)
            {
                return new Console\PublishCommand($app['asset.publisher']);
            });

        if(version_compare($app::VERSION, '4.1', '>=')){
            $app->middleware('Barryvdh\Debugbar\Middleware', array($app));
        }else{
            $app->after(function ($request, $response) use($app)
            {
                $debugbar = $app['debugbar'];
                $debugbar->modifyResponse($request, $response);
            });
        }

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('debugbar', 'command.debugbar.publish');
    }

}
