<?php
// Autentikointi ja roolien hallinta DigiTaidot Kuntoon! -sivustolle
require_once 'db.php';

class Auth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Kirjaudu sisään
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, email, password, role, subscription_active, subscription_end FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['subscription_active'] = $user['subscription_active'];
                $_SESSION['subscription_end'] = $user['subscription_end'];
                
                return true;
            }
            return false;
        } catch(PDOException $e) {
            error_log("Kirjautumisvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Rekisteröidy
    public function register($name, $email, $password) {
        try {
            // Tarkista, onko sähköposti jo käytössä
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return "Sähköpostiosoite on jo käytössä.";
            }

            // Salasana bcrypt-salauksella
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $hashed_password]);

            return true;
        } catch(PDOException $e) {
            error_log("Rekisteröintivirhe: " . $e->getMessage());
            return "Rekisteröinti epäonnistui. Yritä myöhemmin uudelleen.";
        }
    }

    // Kirjaudu ulos
    public function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }

    // Tarkista, onko käyttäjä kirjautunut
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Tarkista, onko käyttäjä admin
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'ADMIN';
    }

    // Tarkista, onko käyttäjällä aktiivinen tilaus
    public function hasActiveSubscription() {
        // Admin pääsee aina sisään
        if ($this->isAdmin()) {
            return true;
        }

        // Tarkista, onko käyttäjä kirjautunut
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Tarkista session-tiedot ensin
        if (isset($_SESSION['subscription_active']) && $_SESSION['subscription_active']) {
            if (isset($_SESSION['subscription_end'])) {
                $end_date = new DateTime($_SESSION['subscription_end']);
                $today = new DateTime();
                
                if ($end_date > $today) {
                    return true;
                } else {
                    // Tilaus vanhentunut, päivitä tietokanta
                    $this->updateSubscriptionStatus();
                    return false;
                }
            }
        }

        return false;
    }

    // Päivitä tilauksen tila tietokannasta
    private function updateSubscriptionStatus() {
        try {
            $stmt = $this->pdo->prepare("SELECT subscription_active, subscription_end FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['subscription_active'] = $user['subscription_active'];
                $_SESSION['subscription_end'] = $user['subscription_end'];
            }
        } catch(PDOException $e) {
            error_log("Tilauksen tilan päivitysvirhe: " . $e->getMessage());
        }
    }

    // Aktivoi käyttäjän tilaus
    public function activateSubscription($user_id, $subscription_id = null) {
        try {
            $end_date = date('Y-m-d', strtotime('+1 month'));
            
            $stmt = $this->pdo->prepare("UPDATE users SET subscription_active = 1, subscription_end = ? WHERE id = ?");
            $stmt->execute([$end_date, $user_id]);

            // Päivitä session-tiedot
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
                $_SESSION['subscription_active'] = true;
                $_SESSION['subscription_end'] = $end_date;
            }

            return true;
        } catch(PDOException $e) {
            error_log("Tilauksen aktivointivirhe: " . $e->getMessage());
            return false;
        }
    }

    // Hae käyttäjän tiedot
    public function getUser($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Käyttäjätietojen hakuvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Hae kaikki käyttäjät (admin)
    public function getAllUsers() {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, email, role, subscription_active, subscription_end, created_at FROM users ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Käyttäjien hakuvirhe: " . $e->getMessage());
            return [];
        }
    }
}

// Luodaan globaali auth-instanssi
$auth = new Auth($pdo);
?>
