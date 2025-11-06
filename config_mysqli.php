<?php
/**
 * Alternative Database Configuration using MySQLi
 * Use this if PDO MySQL driver is not available
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$dbname = 'hcc_asset_management';
$username = 'root';
$password = '';

// Try PDO first
if (extension_loaded('pdo_mysql')) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        define('DB_TYPE', 'PDO');
        
    } catch (PDOException $e) {
        die("PDO Connection failed: " . $e->getMessage());
    }
} 
// Fallback to MySQLi
elseif (extension_loaded('mysqli')) {
    $mysqli = new mysqli($host, $username, $password, $dbname);
    
    if ($mysqli->connect_error) {
        die("MySQLi Connection failed: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    define('DB_TYPE', 'MySQLi');
    
    // Create a PDO-like wrapper for MySQLi
    class MySQLiPDOWrapper {
        private $mysqli;
        
        public function __construct($mysqli) {
            $this->mysqli = $mysqli;
        }
        
        public function prepare($sql) {
            return new MySQLiStatementWrapper($this->mysqli, $sql);
        }
        
        public function query($sql) {
            $result = $this->mysqli->query($sql);
            if (!$result) {
                throw new Exception($this->mysqli->error);
            }
            return new MySQLiResultWrapper($result);
        }
        
        public function lastInsertId() {
            return $this->mysqli->insert_id;
        }
        
        public function setAttribute($attr, $value) {
            // Compatibility method - does nothing for MySQLi
            return true;
        }
        
        public function beginTransaction() {
            return $this->mysqli->begin_transaction();
        }
        
        public function commit() {
            return $this->mysqli->commit();
        }
        
        public function rollBack() {
            return $this->mysqli->rollback();
        }
    }
    
    class MySQLiStatementWrapper {
        private $mysqli;
        private $sql;
        private $stmt;
        
        public function __construct($mysqli, $sql) {
            $this->mysqli = $mysqli;
            $this->sql = $sql;
        }
        
        public function execute($params = []) {
            $this->stmt = $this->mysqli->prepare($this->sql);
            if (!$this->stmt) {
                throw new Exception($this->mysqli->error);
            }
            
            if (!empty($params)) {
                $types = str_repeat('s', count($params));
                $this->stmt->bind_param($types, ...$params);
            }
            
            if (!$this->stmt->execute()) {
                throw new Exception($this->stmt->error);
            }
            
            return true;
        }
        
        public function fetch() {
            if (!$this->stmt) return false;
            $result = $this->stmt->get_result();
            if (!$result) return false;
            return $result->fetch_assoc();
        }
        
        public function fetchAll() {
            if (!$this->stmt) return [];
            $result = $this->stmt->get_result();
            if (!$result) return [];
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        public function rowCount() {
            return $this->stmt ? $this->stmt->affected_rows : 0;
        }
    }
    
    class MySQLiResultWrapper {
        private $result;
        
        public function __construct($result) {
            $this->result = $result;
        }
        
        public function fetch() {
            return $this->result->fetch_assoc();
        }
        
        public function fetchAll() {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }
    }
    
    // Create PDO-compatible object
    $pdo = new MySQLiPDOWrapper($mysqli);
    
} else {
    die("ERROR: Neither PDO MySQL nor MySQLi extension is available.<br><br>" .
        "<strong>Please enable one of these extensions:</strong><br>" .
        "1. Open XAMPP Control Panel<br>" .
        "2. Click 'Config' next to Apache → 'PHP (php.ini)'<br>" .
        "3. Find and uncomment (remove semicolon):<br>" .
        "&nbsp;&nbsp;&nbsp;<code>extension=pdo_mysql</code><br>" .
        "&nbsp;&nbsp;&nbsp;<code>extension=mysqli</code><br>" .
        "4. Save and restart Apache<br><br>" .
        "<a href='check_and_fix_connection.php'>Run Diagnostic Tool</a>");
}

// Include all the helper functions from original config.php
require_once __DIR__ . '/config.php';

echo "<!-- Database connection established using " . DB_TYPE . " -->\n";
?>
