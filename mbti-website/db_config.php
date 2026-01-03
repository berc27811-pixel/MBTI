<?php
// 数据库配置文件 - 统一管理数据库连接
class DatabaseConfig {
    private static $instance = null;
    private $connection;
    
    // 数据库配置信息
    // 注意：生产环境建议使用环境变量或单独的配置文件（不提交到版本控制）
    private $config = [
        'host' => 'localhost',
        'dbname' => 'mbti_dewater_icu',
        'username' => 'mbti_dewater_icu',
        'password' => 'fTasYKzah8mbCEwa',
        'charset' => 'utf8mb4'
    ];
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->connection = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
        } catch (PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }
    
    // 防止克隆
    private function __clone() {}
    
    // 防止反序列化
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * 验证表名和列名，防止SQL注入
     * 只允许字母、数字和下划线
     */
    private function validateIdentifier($identifier) {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
            throw new Exception("无效的标识符: " . $identifier);
        }
        return $identifier;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("SQL查询失败: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("数据库查询失败: " . $e->getMessage());
        }
    }
    
    /**
     * 插入数据
     * @param string $table 表名（会被验证）
     * @param array $data 数据数组，键为列名，值为数据
     * @return int 插入的ID
     */
    public function insert($table, $data) {
        if (empty($data)) {
            throw new Exception("插入数据不能为空");
        }
        
        // 验证表名
        $table = $this->validateIdentifier($table);
        
        // 验证并转义列名
        $columns = [];
        $placeholders = [];
        foreach (array_keys($data) as $column) {
            $validatedColumn = $this->validateIdentifier($column);
            $columns[] = $validatedColumn;
            $placeholders[] = ":{$validatedColumn}";
        }
        
        $columnsStr = implode(', ', $columns);
        $placeholdersStr = implode(', ', $placeholders);
        $sql = "INSERT INTO `{$table}` ({$columnsStr}) VALUES ({$placeholdersStr})";
        
        $this->query($sql, $data);
        return $this->connection->lastInsertId();
    }
    
    /**
     * 更新数据
     * @param string $table 表名（会被验证）
     * @param array $data 要更新的数据
     * @param string $where WHERE条件（使用命名占位符，如 "id = :id"）
     * @param array $whereParams WHERE条件的参数
     * @return int 受影响的行数
     */
    public function update($table, $data, $where, $whereParams = []) {
        if (empty($data)) {
            throw new Exception("更新数据不能为空");
        }
        
        // 验证表名
        $table = $this->validateIdentifier($table);
        
        // 验证并转义列名
        $set = [];
        foreach (array_keys($data) as $column) {
            $validatedColumn = $this->validateIdentifier($column);
            $set[] = "`{$validatedColumn}` = :{$validatedColumn}";
        }
        $setClause = implode(', ', $set);
        
        $sql = "UPDATE `{$table}` SET {$setClause} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    /**
     * 关闭数据库连接
     */
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }
}
?>
