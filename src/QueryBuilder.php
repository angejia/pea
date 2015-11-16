<?php namespace Lvht\Pea;

use Illuminate\Database\Query\Builder;

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

    public function get($columns = ['*'])
    {
        if ($this->model->needCache())
        {
            return parent::get($columns);
        }
        // TODO 查询主键列表
        // TODO 根据主键列表获取缓存
        // TODO 根据未命中的主键列表查询数据库
        // TODO 更新未命中的主键缓存
        // TODO 返回查询结果
    }

    public function delete($id = null)
    {
        if ($this->model->needCache()) {
            // TODO 清空表级缓存
            // TODO 查找受影响的 ID，清空相关行级缓存
        }

        return parent::delete($id);
    }

    public function update(array $values)
    {
        if ($this->model->needCache()) {
            // TODO 清空表级缓存
            // TODO 查找受影响的 ID，清空相关行级缓存
        }
        return parent::update($values);
    }

    public function insert(array $values)
    {
        if ($this->model->needCache()) {
            // TODO 清空表级缓存
        }
        return parent::insert($values);
    }
}
