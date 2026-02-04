<?php
/**
 * Secure Database Connection Class
 * Uses prepared statements to prevent SQL injection
 */

class Database {
    private $conn;
    private static $instance = null;

    // Database credentials - loaded from config or environment
    private $host;
    private $username;
    private $password;
    private $database;

    /**
     * Constructor - establish connection
     */
    public function __construct() {
        // Load from environment variables (for free hosting) or use defaults
        $this->host = getenv('MYSQLHOST') ?: 'sql203.yzz.me';
        $this->username = getenv('MYSQLUSER') ?: 'yzzme_41042304';
        $this->password = getenv('MYSQLPASSWORD') ?: '';
        $this->database = getenv('MYSQLDATABASE') ?: 'yzzme_41042304_loan_db';

        // Check if config.php exists and load from there
        $configFile = __DIR__ . '/../config.php';
        if (file_exists($configFile)) {
            include $configFile;
            if (isset($db_host)) $this->host = $db_host;
            if (isset($db_user)) $this->username = $db_user;
            if (isset($db_pass)) $this->password = $db_pass;
            if (isset($db_name)) $this->database = $db_name;
        }

        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->conn->connect_error) {
            error_log("Database connection failed: " . $this->conn->connect_error);
            die("Connection failed. Please try again later.");
        }
        
        // Set charset to prevent encoding attacks
        $this->conn->set_charset("utf8mb4");
    }
    
    /**
     * Singleton pattern - get instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get connection object (for backward compatibility)
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute a prepared statement with parameters
     * @param string $sql SQL query with placeholders
     * @param string $types Parameter types (i=int, s=string, d=double, b=blob)
     * @param array $params Parameters to bind
     * @return mysqli_result|bool
     */
    public function execute($sql, $types = '', $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $queryResult = $stmt->get_result();
        $stmt->close();
        
        return $queryResult !== false ? $queryResult : $result;
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $types = '', $params = []) {
        $result = $this->execute($sql, $types, $params);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $types = '', $params = []) {
        $result = $this->execute($sql, $types, $params);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    /**
     * Insert and return last insert ID
     */
    public function insert($sql, $types = '', $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $insertId = $this->conn->insert_id;
        $stmt->close();
        
        return $result ? $insertId : false;
    }
    
    /**
     * Update and return affected rows
     */
    public function update($sql, $types = '', $params = []) {
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return false;
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        $result = $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $result ? $affectedRows : false;
    }
    
    /**
     * Delete and return affected rows
     */
    public function delete($sql, $types = '', $params = []) {
        return $this->update($sql, $types, $params);
    }
    
    /**
     * Get count
     */
    public function count($sql, $types = '', $params = []) {
        $result = $this->fetchOne($sql, $types, $params);
        if ($result) {
            return array_values($result)[0];
        }
        return 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
    
    /**
     * Escape string (use only when prepared statements aren't possible)
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Get last error
     */
    public function getError() {
        return $this->conn->error;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->conn->close();
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// For backward compatibility - create $conn variable
$db = Database::getInstance();
$conn = $db->getConnection();
