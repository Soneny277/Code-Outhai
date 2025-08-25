<?php
class Database {
    private $host = "localhost";
    private $db_name = "hoteloudomsup";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8mb4");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "ການເຊື່ອມຕໍ່ຖານຂໍ້ມູນລົ້ມເຫລວ: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?> 