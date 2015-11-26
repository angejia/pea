<?php namespace Angejia\Pea;

interface Meta
{
    /**
     * 获取缓存前缀
     *
     * @param string $db 数据库名称
     * @param string $table 表名
     * @param bool $isForTable 是否需要获取表级缓存前缀
     *
     * @return string 前缀字符串
     */
    function prefix($db, $table, $isForTable = false);

    /**
     * 刷新表级缓存版本，调用此方法让所有表级缓存过期
     *
     * @param string $db 数据库名称
     * @param string $table 表名
     */
    function flush($db, $table);

    /**
     * 刷新所有缓存，调用此方法让所有缓存过期。
     * 仅用于执行 Migration 以后调用
     *
     * @param string $db 数据库名称
     * @param string $table 表名
     */
    function flushAll($db, $table);
}
