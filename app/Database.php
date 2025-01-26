<?php

declare(strict_types=1);

namespace Api;

require 'vendor/autoload.php';

use Dotenv\Dotenv;

class Database {

    public $hostMaster;
    public $port;
    public $db;
    public $user;
    public $pass;

    function __construct() {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();

        $this->hostMaster = $_ENV['POSTGRES_HOST'];

        $this->port = $_ENV['POSTGRES_PORT'];
        $this->db = $_ENV['POSTGRES_DB'];
        $this->user = $_ENV['POSTGRES_USER'];
        $this->pass = $_ENV['POSTGRES_PASSWORD'];
    }

    public function getConnection() {
        try {
            $pdo = new \PDO("pgsql:host=$this->hostMaster;port=$this->port;dbname=$this->db", $this->user, $this->pass);

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            return $pdo;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
            exit();
        }
    }
}
?>