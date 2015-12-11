<?php namespace Angejia\Pea;

use Illuminate\Support\Facades\Facade;

class SchemaFacade extends Facade
{
    /**
     * Get a schema builder instance for a connection.
     *
     * @param  string  $name
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function connection($name)
    {
        return self::getSchemaBuilder($name);
    }

    /**
     * Get a schema builder instance for the default connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected static function getFacadeAccessor()
    {
        return self::getSchemaBuilder();
    }

    private static function getSchemaBuilder($name = null)
    {
        $builder = static::$app['db']->connection($name)->getSchemaBuilder();

        $builder->blueprintResolver(function ($table, $callback) {
            $blueprint = new Blueprint($table, $callback);
            $blueprint->setMeta(static::$app[Meta::class]);

            return $blueprint;
        });

        return $builder;
    }
}
