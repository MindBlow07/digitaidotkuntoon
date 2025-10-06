<?php
// Tietokantayhteys DigiTaidot Kuntoon! -sivustolle
require_once '../config.php';

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    // Yhdistä tietokantaan
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Tietokantayhteysvirhe: " . $exception->getMessage());
            die("Tietokantayhteydessä on ongelma. Yritä myöhemmin uudelleen.");
        }

        return $this->conn;
    }

    // Sulje yhteys
    public function closeConnection() {
        $this->conn = null;
    }
}

// Luodaan globaali tietokanta-instanssi
$database = new Database();
$pdo = $database->getConnection();
?>
