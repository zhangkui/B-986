<?php
/**
 * 数据库连接配置
 */

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: 'db';
            $port = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'diaocha';
            $user = getenv('DB_USER') ?: 'diaocha';
            $pass = getenv('DB_PASS') ?: 'diaocha';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $maxRetries = 30;
            $retryDelay = 2;

            for ($i = 0; $i < $maxRetries; $i++) {
                try {
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                    ]);
                    break;
                } catch (PDOException $e) {
                    if ($i === $maxRetries - 1) {
                        error_log("Database connection failed after {$maxRetries} attempts: " . $e->getMessage());
                        throw $e;
                    }
                    error_log("DB connection attempt " . ($i + 1) . " failed, retrying in {$retryDelay}s...");
                    sleep($retryDelay);
                }
            }
        }
        return self::$instance;
    }
}
