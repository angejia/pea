<?php namespace Angejia\Pea;

interface Cache
{
    /**
     * 批量查询缓存内容
     *
     * @param array $keys 缓存索引列表
     *
     * @return array 缓存内容键值对，例如
     * [
     *   'key1' => 'value1',
     *   'key2' => 'value2',
     * ]
     */
    function get($keys);

    /**
     * 批量设置缓存内容
     *
     * @param array $key_value 待缓存键值对
     */
    function set($keyValue);

    /**
     * 清理缓存内容
     *
     * @param array $keys 缓存索引列表
     */
    function del($keys);
}
