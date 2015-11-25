<?php namespace Angejia\Pea;

use Illuminate\Database\Query\Builder;
use Illuminate\Container\Container;

class QueryBuilder extends Builder
{
    /**
     * @var Model
     */
    private $model;

    public function setModel(Model $model)
    {
        $this->model = $model;
    }

    /**
     * @return Cache
     */
    protected function getCache()
    {
        return Container::getInstance()->make(Cache::class);
    }

    /**
     * @return Meta
     */
    protected function getMeta()
    {
        return Container::getInstance()->make(Meta::class);
    }

    public function get($columns = ['*'])
    {
        if (!$this->model->needCache())
        {
            return parent::get($columns);
        }

        if ($this->isAwful()) {
            return $this->getAwful($columns);
        } elseif ($this->isNormal()) {
            return $this->getNormal($columns);
        } else {
            return $this->getSimple($columns);
        }
    }

    private function getAwful($columns)
    {
        $key = $this->buildTableCacheKey($this->toSql(), $this->getBindings());
        $cache = $this->getCache();
        $result = $cache->get([$key]);
        if (array_key_exists($key, $result)) {
            return $result[$key];
        }

        $result = parent::get($columns);
        $cache->set([
            $key => $result,
        ]);

        return $result;
    }

    /**
     * 判断当前查询是否未「复杂查询」，判断标准
     * 1. 含有 max, sum 等汇聚函数
     * 2. 包含 distinct 指令
     * 3. 包含分组
     * 4. 包含连表
     * 5. 包含联合
     * 6. 包含子查询
     * 7. 包含原生（raw）语句
     * 8. 包含排序 TODO 优化此类情形
     *
     * 复杂查询使用表级缓存，命中率较低
     */
    private function isAwful()
    {
        return $this->aggregate
            or $this->distinct
            or $this->groups
            or $this->joins
            or $this->orders
            or $this->unions
            or array_key_exists('Exists', $this->wheres)
            or array_key_exists('InSub', $this->wheres)
            or array_key_exists('NotExists', $this->wheres)
            or array_key_exists('NotInSub', $this->wheres)
            or array_key_exists('Sub', $this->wheres)
            or array_key_exists('raw', $this->wheres);
    }

    private function getNormal($columns)
    {
        $primaryKeyName = $this->model->primaryKey();
        // 查询主键列表
        $rows = $this->getAwful([$primaryKeyName]);
        $ids = array_map(function ($row) use($primaryKeyName) {
            return $row->$primaryKeyName;
        }, $rows);

        // 没查到结果则直接返回空数组
        if (!$ids) {
            return [];
        }

        // 根据主键查询结果
        $this->wheres = [];
        $this->limit = null;
        $this->offset = null;
        $this->whereIn($primaryKeyName, $ids);
        $rows = $this->getSimple($columns);

        return $rows;
    }

    /**
     * 判断当前查询是否未「普通查询」
     *
     * 普通查询需要转化成简单查询
     */
    private function isNormal()
    {
        return !$this->isAwful() && !$this->isSimple();
    }

    /**
     * 简单查询，只根据主键过滤结果集
     */
    private function getSimple($columns)
    {
        $primaryKeyName = $this->model->primaryKey();
        $cacheKeys = $this->buildCacheKeys();

        $cache = $this->getCache();
        $cachedRows = $cache->get(array_values($cacheKeys));
        foreach ($cachedRows as $row) {
            unset($cacheKeys[$row->$primaryKeyName]);
        }
        // TODO 如何处理顺序
        $cachedRows = array_values($cachedRows);

        $missedIds = array_keys($cacheKeys);
        if (!$missedIds) {
            return $cachedRows;
        }

        $originWheres = $this->wheres;
        $this->wheres = [];
        $this->bindings['where'] = [];
        $this->whereIn($primaryKeyName, $missedIds);

        $missedRows = parent::get($columns);
        $this->wheres = $originWheres;

        $toCachRows = [];
        foreach ($missedRows as $row) {
            $toCachRows[$this->buildRowCacheKey($row->$primaryKeyName)] = $row;
        }
        if ($toCachRows) {
            $cache->set($toCachRows);
        }

        return array_merge($cachedRows, $missedRows);
    }

    private function buildCacheKeys()
    {
        $where = current($this->wheres);
        if ($where['type'] == 'In') {
            $ids = $where['values'];
        } else {
            $ids = [$where['value']];
        }

        $cacheKeys = [];
        foreach ($ids as $id) {
            $cacheKeys[$id] = $this->buildRowCacheKey($id);
        }

        return $cacheKeys;
    }

    /**
     * 「简单查询」就是只根据主键过滤结果集的查询，有以下两种形式：
     * 1. select * from foo where id = 1;
     * 2. select * from foo where id in (1, 2, 3);
     */
    private function isSimple()
    {
        if ($this->isAwful()) {
            return false;
        }

        if (!$this->wheres) {
            return false;
        }

        if (count($this->wheres) > 1) {
            return false;
        }

        $where = current($this->wheres);

        $id = $this->model->primaryKey();
        $tableId = $this->model->table() . '.' . $this->model->primaryKey();
        if (!in_array($where['column'], [$id, $tableId])) {
            return false;
        }

        if ($where['type'] === 'In') {
            return true;
        }

        if ($where['type'] === 'Basic') {
            if ($where['operator'] === '=') {
                return true;
            }
        }

        return false;
    }

    public function delete($id = null)
    {
        if ($this->model->needCache()) {
            // 清空表级缓存
            $meta = $this->getMeta();
            $meta->flush($this->db(), $this->model->table());
            $this->flushAffectingRowCache();
        }

        return parent::delete($id);
    }

    /**
     * 查找受影响的 ID，清空相关行级缓存
     */
    private function flushAffectingRowCache()
    {
        $keyName = $this->model->primaryKey();
        $toDeleteRows = parent::get();
        $cacheKeys = array_map(function ($row) use ($keyName) {
            return $this->buildRowCacheKey($row->$keyName);
        }, $toDeleteRows);

        $cache = $this->getCache();
        $cache->del($cacheKeys);
    }

    public function update(array $values)
    {
        if ($this->model->needCache()) {
            // 清空表级缓存
            $meta = $this->getMeta();
            $meta->flush($this->db(), $this->model->table());

            $this->flushAffectingRowCache();
        }
        return parent::update($values);
    }

    public function insert(array $values)
    {
        if ($this->model->needCache()) {
            // 清空表级缓存
            $meta = $this->getMeta();
            $meta->flush($this->db(), $this->model->table());
        }

        return parent::insert($values);
    }

    public function insertGetId(array $values, $sequence = null)
    {
        if ($this->model->needCache()) {
            // 清空表级缓存
            $meta = $this->getMeta();
            $meta->flush($this->db(), $this->model->table());
        }

        return parent::insertGetId($values, $sequence);
    }

    /**
     * 获取当前查询所用的数据库名称
     */
    private function db()
    {
        return $this->connection->getDatabaseName();
    }

    /**
     * 构造行级缓存索引
     */
    private function buildRowCacheKey($keyValue)
    {
        $meta = $this->getMeta();
        $parts = [
            $meta->prefix($this->db(), $this->model->table()),
            $keyValue,
        ];

        return md5(implode(':', $parts));
    }

    /**
     * 构造表级缓存索引
     */
    private function buildTableCacheKey($sql, $bindings)
    {
        $meta = $this->getMeta();
        $parts = [
            $meta->prefix($this->db(), $this->model->table(), true),
            $sql,
            json_encode($bindings),
        ];

        return md5(implode(':', $parts));
    }
}
