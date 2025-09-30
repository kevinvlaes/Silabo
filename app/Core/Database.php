<?php
class Database {
    private static $pdo;
    public static function pdo() {
        if (!self::$pdo) {
            $config = require __DIR__ . '/../Config/config.php';
            $dsn = "mysql:host=".$config['db']['host'].";dbname=".$config['db']['name'].";charset=utf8";
            self::$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass']);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        return self::$pdo;
    }
}
