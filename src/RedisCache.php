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
        $keyValue = array_combine($keys, $this->redis->mget($keys));
        $keyValue = array_filter($keyValue, function ($value) {
            return $value;
        });

        return $keyValue;
    }

    public function set($keyValue)
    {
        $keyValue = array_filter($keyValue, function ($value) {
            return $value;
        });
        return $this->redis->mset($keyValue);
    }

    public function del($keys)
    {
        return $this->redis->del($keys);
    }
}
