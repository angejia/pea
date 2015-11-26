<?php namespace Angejia\Pea;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Builder;

abstract class Model extends EloquentModel
{
    protected $needCache = false;

    public function needCache()
    {
        return $this->needCache;
    }

    public function primaryKey()
    {
        return $this->primaryKey;
    }

    public function table()
    {
        return $this->table;
    }

    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $queryBuilder = new QueryBuilder(
            $conn, $grammar, $conn->getPostProcessor());
        $queryBuilder->setModel($this);

        return $queryBuilder;
    }

    public function newEloquentBuilder($query)
    {
        $builder = new Builder($query);
        $builder->macro('key', function (Builder $builder) {
            return $builder->getQuery()->key();
        });

        return $builder;
    }
}
