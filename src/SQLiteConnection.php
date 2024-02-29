<?php

namespace SebKay\Touchstone;

class SQLiteConnection
{
    protected \PDO $pdo;

    public function connect(): \PDO
    {
        return $this->pdo ??= new \PDO('sqlite:'.SQLITE_FILE_PATH);
    }
}
