<?php namespace Angejia\Pea;

use Mockery as M;
use Illuminate\Contracts\Redis\Database as Redis;
use Angejia\Pea\RedisMeta;

class MetaTest extends TestCase
{
    public function testFlush()
    {
        $redis = M::mock(Redis::class);
        $redis->shouldReceive('incr')->with('1031d621441d2f689f95870d86225cf2');

        $meta = new RedisMeta($redis);
        $meta->flush('angejia', 'user');
    }

    public function testFlushAll()
    {
        $redis = M::mock(Redis::class);
        $redis->shouldReceive('incr')->with('2d23e940e190c4fefe3955fe8cf5c8a8');

        $meta = new RedisMeta($redis);
        $meta->flushAll('angejia', 'user');
    }

    public function testPrefixForRow()
    {
        $redis = M::mock(Redis::class);
        $redis->shouldReceive('get')->with('2d23e940e190c4fefe3955fe8cf5c8a8')
            ->andReturn(1);

        $meta = new RedisMeta($redis);
        $prefix = $meta->prefix('angejia', 'user');
        $this->assertEquals('pea:angejia:user:1', $prefix);
    }

    public function testPrefixForTable()
    {
        $redis = M::mock(Redis::class);
        // schema version
        $redis->shouldReceive('get')->with('2d23e940e190c4fefe3955fe8cf5c8a8')
            ->andReturn(1);
        // update version
        $redis->shouldReceive('get')->with('1031d621441d2f689f95870d86225cf2')
            ->andReturn(6);

        $meta = new RedisMeta($redis);
        $prefix = $meta->prefix('angejia', 'user', true);
        $this->assertEquals('pea:angejia:user:1:6', $prefix);
    }
}
