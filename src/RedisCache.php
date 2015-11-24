<?php namespace Lvht\Pea;

use Illuminate\Contracts\Redis\Database as Redis;

class RedisCache implements Cache
{
    /**
     * @var Redis
     */
    private $redis;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function get($keys)
    {
        return array_combine($keys, $this->redis->mget($keys));
    }

    public function set($keyValue)
    {
        return $this->redis->mset($keyValue);
    }

    public function del($keys)
    {
        return $this->redis->del($keys);
    }
}
