<?php namespace Angejia\Pea;

use Illuminate\Foundation\AliasLoader;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot()
    {
        $loader = AliasLoader::getInstance();
        $loader->alias('Schema', SchemaFacade::class);
    }

    public function register()
    {
        // TODO 缓存 redis 实例可以配置
        $this->app->singleton(Meta::class, function () {
            return new RedisMeta($this->getRedis());
        });
        $this->app->singleton(Cache::class, function () {
            return new RedisCache($this->getRedis());
        });
    }

    /**
     * 默认取名称为 pea 的 Redis 实例；如果没有，则取 default 。
     */
    private function getRedis()
    {
        $redis = $this->app->make('redis');
        return $redis->connection('pea') ?: $redis->connection();
    }
}
