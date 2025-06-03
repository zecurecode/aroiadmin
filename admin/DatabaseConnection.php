<?php
/**
 * Modern Database Connection Handler
 * Supports multiple connection methods with fallbacks
 */
class DatabaseConnection {
    private $config;
    private $connection;
    private $connection_type;
    private static $instance = null;
    
    public function __construct($config) {
        $this->config = $config;
        $this->connect();
    }
    
    public static function getInstance($config = null) {
        if (self::$instance === null) {
            if ($config === null) {
                throw new Exception("Database configuration required for first instance");
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    private function connect() {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $username = $this->config['username'];
        $password = $this->config['password'];
        $database = $this->config['database'];
        
        // Try connection methods in order of preference
        $methods = [
            'pdo_mysql' => 'connectPDO',
            'mysqli' => 'connectMysqli'
        ];
        
        $errors = [];
        
        foreach ($methods as $extension => $method) {
            if (extension_loaded($extension)) {
                try {
                    $this->$method($host, $port, $username, $password, $database);
                    $this->connection_type = $extension;
                    return;
                } catch (Exception $e) {
                    $errors[$extension] = $e->getMessage();
                    continue;
                }
            } else {
                $errors[$extension] = "Extension not loaded";
            }
        }
        
        // If we get here, all methods failed
        throw new Exception("All database connection methods failed: " . json_encode($errors));
    }
    
    private function connectPDO($host, $port, $username, $password, $database) {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10
        ];
        
        $this->connection = new PDO($dsn, $username, $password, $options);
    }
    
    private function connectMysqli($host, $port, $username, $password, $database) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->connection = new mysqli($host, $username, $password, $database, $port);
        $this->connection->set_charset("utf8mb4");
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function getConnectionType() {
        return $this->connection_type;
    }
    
    public function query($sql, $params = []) {
        if ($this->connection_type === 'pdo_mysql') {
            return $this->queryPDO($sql, $params);
        } else {
            return $this->queryMysqli($sql, $params);
        }
    }
    
    private function queryPDO($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    private function queryMysqli($sql, $params = []) {
        if (empty($params)) {
            return $this->connection->query($sql);
        }
        
        $stmt = $this->connection->prepare($sql);
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Assume all strings for simplicity
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    public function lastInsertId() {
        if ($this->connection_type === 'pdo_mysql') {
            return $this->connection->lastInsertId();
        } else {
            return $this->connection->insert_id;
        }
    }
    
    public function escape($value) {
        if ($this->connection_type === 'pdo_mysql') {
            return $this->connection->quote($value);
        } else {
            return "'" . $this->connection->real_escape_string($value) . "'";
        }
    }
    
    public function getServerInfo() {
        if ($this->connection_type === 'pdo_mysql') {
            return [
                'version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
                'connection_type' => 'PDO MySQL'
            ];
        } else {
            return [
                'version' => $this->connection->server_info,
                'connection_type' => 'MySQLi'
            ];
        }
    }
    
    public function testConnection() {
        try {
            if ($this->connection_type === 'pdo_mysql') {
                $stmt = $this->connection->query("SELECT VERSION() as version, DATABASE() as current_db, USER() as current_user");
                return $stmt->fetch();
            } else {
                $result = $this->connection->query("SELECT VERSION() as version, DATABASE() as current_db, USER() as current_user");
                return $result->fetch_assoc();
            }
        } catch (Exception $e) {
            throw new Exception("Connection test failed: " . $e->getMessage());
        }
    }
    
    public function getTables() {
        try {
            if ($this->connection_type === 'pdo_mysql') {
                $stmt = $this->connection->query("SHOW TABLES");
                return $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $result = $this->connection->query("SHOW TABLES");
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                return $tables;
            }
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function close() {
        if ($this->connection) {
            if ($this->connection_type === 'pdo_mysql') {
                $this->connection = null;
            } else {
                $this->connection->close();
            }
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}
?> 