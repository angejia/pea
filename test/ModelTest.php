<?php namespace Lvht\Pea;

use Mockery as M;
use Lvht\Pea\Cache;
use Lvht\Pea\Meta;
use Illuminate\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\ConnectionResolverInterface;

class ModelTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // 构建数据库模拟层
        $conn = M::mock(ConnectionInterface::class);
        // 模拟连接 angejia 数据库
        $conn->shouldReceive('getDatabaseName')->andReturn('angejia');
        $conn->shouldReceive('getQueryGrammar')->andReturn(new Grammar);
        $conn->shouldReceive('getPostProcessor')->andReturn(new Processor);

        $this->conn = $conn;

        // 让所有 Model 使用我们伪造的数据库连接
        $resolver = M::mock(ConnectionResolverInterface::class);
        $resolver->shouldReceive('connection')
            ->andReturnUsing(function () {
                return $this->conn;
            });
        User::setConnectionResolver($resolver);

        // 模拟 Meta 服务
        $meta = M::mock(Meta::class);
        $meta->shouldReceive('prefix')
            // 查找 angejia.user 表主键缓存 key 前缀
            ->with('angejia', 'user')
            // 缓存 key 前缀全部使用空字符串 ''
            ->andReturn('');
        $meta->shouldReceive('prefix')
            // 查找 angejia.user 表主键缓存 key 前缀
            ->with('angejia', 'user', true)
            // 缓存 key 前缀全部使用空字符串 ''
            ->andReturn('');
        $this->meta = $meta;

        // 模拟 Cache 服务
        $cache = M::mock(Cache::class);
        $this->cache = $cache;

        // 注入依赖的服务
        $this->app->bind(Meta::class, function () {
            return $this->meta;
        });
        $this->app->bind(Cache::class, function () {
            return $this->cache;
        });
    }

    public function testModelTable()
    {
        $user = new User;
        $this->assertEquals('user', $user->table());
    }

    public function testModelNeedCache()
    {
        $user = new User;
        $this->assertTrue($user->needCache());
    }

    public function testOneCachedSimpleGet()
    {
        $this->cache->shouldReceive('get')
            // 查询 id 为 1 的缓存
            ->with([
                '3558193cd9818af7fe4d2c2f5bd9d00f',
            ])
            // 模拟全部命中缓存
            ->andReturn([
                '3558193cd9818af7fe4d2c2f5bd9d00f' => (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);


        // 查询 id 为 1 的记录，应该命中缓存
        $u1 = User::find(1);

        $this->assertEquals('海涛', $u1->name);
    }

    public function testAllCachedSimpleGet()
    {
        $this->cache->shouldReceive('get')
            // 查询 id 为 1 和 2 的缓存
            ->with([
                '3558193cd9818af7fe4d2c2f5bd9d00f',
                '343a10e6c2480e111dd3e9e564eb7966',
            ])
            // 模拟全部命中缓存
            ->andReturn([
                '3558193cd9818af7fe4d2c2f5bd9d00f' => (object) [ 'id' => 1, 'name' => '海涛', ],
                '343a10e6c2480e111dd3e9e564eb7966' => (object) [ 'id' => 2, 'name' => '涛涛', ],
            ]);


        // 查询 id 为 1 和 2 的记录，应该全部命中缓存
        // TODO 此处顺序是个问题
        list($u1, $u2) = User::find([1, 2]);

        $this->assertEquals('海涛', $u1->name);
        $this->assertEquals('涛涛', $u2->name);
    }

    public function testPartialCachedSimpleGet()
    {
        $this->cache->shouldReceive('get')
            ->with([
                '3558193cd9818af7fe4d2c2f5bd9d00f',
                '343a10e6c2480e111dd3e9e564eb7966',
            ])
            // 缓存中只有 id 为 1 的数据
            ->andReturn([
                '3558193cd9818af7fe4d2c2f5bd9d00f' => (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);

        // 模拟数据库返回查询未命中缓存的数据
        $this->conn->shouldReceive('select')
            ->andReturn([
                (object) [ 'id' => 2, 'name' => '涛涛', ],
            ]);

        // 查询完成后需要将数据写入缓存
        $this->cache->shouldReceive('set')
            ->with([
                '343a10e6c2480e111dd3e9e564eb7966' => (object) [ 'id' => 2, 'name' => '涛涛', ],
            ]);

        list($u1, $u2) = User::find([1, 2]);

        $this->assertEquals('海涛', $u1->name);
        $this->assertEquals('涛涛', $u2->name);
    }

    public function testFlushCacheForInsert()
    {
        $pdo = M::mock('\PDO');
        // 模拟数据库返回自增主键 ID
        $pdo->shouldReceive('lastInsertId')->andReturn(1);
        $this->conn->shouldReceive('getPdo')->andReturn($pdo);
        $this->conn->shouldReceive('insert');

        // 模拟刷新表级缓存
        $this->meta->shouldReceive('flush')->with('angejia', 'user');

        $user = new User;
        $user->name = '海涛';
        $user->save();
        $this->assertEquals(1, $user->id);
    }

    public function testFlushCacheForUpdateOne()
    {
        // 模拟数据库更新操作
        $this->conn->shouldReceive('update');
        // 模拟刷新表级缓存
        $this->meta->shouldReceive('flush')->with('angejia', 'user');
        // 模拟刷新行级缓存
        $this->cache->shouldReceive('del')
            ->with(['3558193cd9818af7fe4d2c2f5bd9d00f']);

        $this->cache->shouldReceive('get')
            // 查询 id 为 1 的缓存
            ->with([
                '3558193cd9818af7fe4d2c2f5bd9d00f',
            ])
            // 模拟全部命中缓存
            ->andReturn([
                '3558193cd9818af7fe4d2c2f5bd9d00f' => (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);

        // 模拟返回受到影响的数据，用于清理缓存
        $this->conn->shouldReceive('select')
            ->andReturn([
                (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);


        $user = User::find(1);
        $user->name = '海涛2';
        $user->save();
    }

    public function testFlushCacheForDeleteOne()
    {
        // 模拟数据库更新
        $this->conn->shouldReceive('delete');

        // 模拟刷新表级缓存
        $this->meta->shouldReceive('flush')->with('angejia', 'user');
        // 模拟刷新行级缓存
        $this->cache->shouldReceive('del')
            ->with(['3558193cd9818af7fe4d2c2f5bd9d00f']);

        // 模拟数据库返回首次查询结果
        $this->cache->shouldReceive('get')
            // 查询 id 为 1 的缓存
            ->with([
                '3558193cd9818af7fe4d2c2f5bd9d00f',
            ])
            // 模拟全部命中缓存
            ->andReturn([
                '3558193cd9818af7fe4d2c2f5bd9d00f' => (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);

        // 模拟返回受到影响的数据，用于清理缓存
        $this->conn->shouldReceive('select')
            ->andReturn([
                (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);

        $user = User::find(1);
        $user->delete();
    }

    /**
     * 复杂查询，命中缓存
     */
    public function testCachedAwfulGet()
    {
        // 模拟数据库返回结果
        $this->conn->shouldReceive('select')
            ->andReturn([
                (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);
        $this->cache->shouldReceive('get')
            ->with([
                'a52d401e05fd1cc438bd070bc4c1c14f',
            ])
            ->andReturn([
                'a52d401e05fd1cc438bd070bc4c1c14f' => [
                    (object) [ 'id' => 1, 'name' => '海涛', ],
                ]
            ]);

        $users = User::where('status', 1)->orderBy('id', 'desc')->get();
        $user0 = $users[0];
        $this->assertEquals(1, $user0->id);
    }

    /**
     * 复杂查询未命中缓存
     */
    public function testMissAwfulGet()
    {
        // 模拟数据库返回结果
        $this->conn->shouldReceive('select')
            ->andReturn([
                (object) [ 'id' => 1, 'name' => '海涛', ],
            ]);
        // 模拟未命中缓存
        $this->cache->shouldReceive('get')
            ->with([
                'a52d401e05fd1cc438bd070bc4c1c14f',
            ])
            ->andReturn([]);
        // 模拟缓存查询结果
        $this->cache->shouldReceive('set')
            ->with([
                'a52d401e05fd1cc438bd070bc4c1c14f' => [
                    (object) [ 'id' => 1, 'name' => '海涛', ],
                ]
            ]);
        $uers = User::where('status', 1)->orderBy('id', 'desc')->get();
    }
}

class User extends Model
{
    protected $table = 'user';
    protected $needCache = true;
}
