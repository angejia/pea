<?php namespace Angejia\Pea;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->app->singleton(Meta::class, function () {
            return new RedisMeta($this->app->make('redis'));
        });
        $this->app->singleton(Cache::class, function () {
            return new RedisCache($this->app->make('redis'));
        });
    }
}
