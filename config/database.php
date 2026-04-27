<?php
class Database {
    private $host = "localhost";
    private $db_name = "url_redirect";
    private $username = "root";
    private $password = "123456";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // 修复：使用正确的变量名 $this->db_name
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            throw new Exception("数据库连接失败: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>