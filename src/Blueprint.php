<?php namespace Angejia\Pea;

use Illuminate\Database\Schema\Blueprint as Base;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;

class Blueprint extends Base
{
    /**
     * @var Meta
     */
    private $meta;

    public function build(Connection $connection, Grammar $grammar)
    {
        $statements = $this->toSql($connection, $grammar);
        if (!$statements) {
            throw new \RuntimeException('migration must be created with Blueprint');
        }

        foreach ($this->toSql($connection, $grammar) as $statement) {
            $connection->statement($statement);
        }

        $db = $connection->getDatabaseName();
        $table = $this->getTable();
        $this->meta->flushAll($db, $table);
    }

    public function setMeta(Meta $meta)
    {
        $this->meta = $meta;
    }
}
