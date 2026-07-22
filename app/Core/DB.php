<?php

namespace App\Core;

use PDO;

class DB
{
    protected static $pdo;

    public static function init(PDO $pdo)
    {
        self::$pdo = $pdo;
    }
    
    public static function getPdo()
    {
        return self::$pdo;
    }

    public static function table($table)
    {
        return new QueryBuilder(self::$pdo, $table);
    }

    public static function raw($sql, $params = [])
    {
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
