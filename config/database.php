<?php
// config/database.php - Database connection singleton

define('DB_HOST', 'localhost');
define('DB_NAME', 'niyawwse_report');
define('DB_USER', 'niyawwse_cyungots');
define('DB_PASS', 'ckqjt(wUC(pC');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("DB Connection Error: " . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed.']));
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->pdo;
    }

    // Convenience: run a prepared statement and return PDOStatement
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Return single row
    public function fetchOne(string $sql, array $params = []): ?array {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    // Return all rows
    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    // Insert and return last insert ID
    public function insert(string $sql, array $params = []): int {
        $this->query($sql, $params);
        return (int)$this->pdo->lastInsertId();
    }

    // Execute update/delete and return affected rows
    public function execute(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }
}
