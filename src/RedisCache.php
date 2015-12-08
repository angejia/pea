<?php namespace Angejia\Pea;

use Predis\Client as Redis;

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
        $pipe = $this->redis->pipeline();

        foreach ($keyValue as $key => $value) {
            if (!is_null($value)) {
                $value = json_encode($value);
                $pipe->setex($key, 600, $value); // 缓存 10 分钟
            }
        }

        return $pipe->execute();
    }

    public function del($keys)
    {
        return $this->redis->del($keys);
    }
}
