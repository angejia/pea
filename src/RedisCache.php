<?php namespace Angejia\Pea;

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
        array_walk($keyValue, function (&$item) {
            $item = json_decode($item);
        });
        $keyValue = array_filter($keyValue, function ($value) {
            return !is_null($value);
        });

        return $keyValue;
    }

    public function set($keyValue)
    {
        $keyValue = array_filter($keyValue, function ($value) {
            return !is_null($value);
        });
        array_walk($keyValue, function (&$item) {
            $item = json_encode($item);
        });

        return $this->redis->mset($keyValue);
    }

    public function del($keys)
    {
        return $this->redis->del($keys);
    }
}
