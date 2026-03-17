<?php
require_once __DIR__ . '/../config.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        mysqli_report(MYSQLI_REPORT_OFF); // disable exceptions, handle manually
        $host = DB_HOST . (defined('DB_PORT') && (int)DB_PORT !== 3306 ? ':' . DB_PORT : '');
        $this->conn = new mysqli($host, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            $this->showDbError($this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4');
    }

    private function showDbError(string $msg): void {
        http_response_code(503);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <title>Service Unavailable — Printworld</title>
        <style>body{font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f5f5;}
        .box{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:40px;max-width:480px;text-align:center;}
        h2{color:#c00;margin-bottom:12px;}p{color:#555;line-height:1.6;}
        .btn{display:inline-block;margin-top:20px;padding:10px 24px;background:#111;color:#fff;text-decoration:none;border-radius:5px;font-size:0.9rem;}
        </style></head><body>
        <div class="box">
          <h2>&#9888; Database Unavailable</h2>
          <p>The database server is not running. Please start <strong>MySQL</strong> in XAMPP Control Panel and try again.</p>
          <a href="javascript:location.reload()" class="btn">&#8635; Retry</a>
        </div></body></html>';
        exit;
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli {
        return $this->conn;
    }

    public function prepare(string $sql): mysqli_stmt|false {
        return $this->conn->prepare($sql);
    }

    public function query(string $sql): mysqli_result|bool {
        return $this->conn->query($sql);
    }

    public function escape(string $value): string {
        return $this->conn->real_escape_string($value);
    }

    public function lastInsertId(): int {
        return $this->conn->insert_id;
    }
}

function db(): mysqli {
    return Database::getInstance()->getConnection();
}
