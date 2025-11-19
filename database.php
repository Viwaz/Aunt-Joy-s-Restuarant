<?php
    class Database {
        private $host = "127.0.0.1";
        private $port = "3307";
        private $db_name = "aunt_joydb";
        private $username = "root";
        private $password ;
        protected $conn;

        public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name,
                $this->port
            );
            $this->conn->set_charset("set names utf8");
        } catch(mysqli_sql_exception $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
        }
    }
?>