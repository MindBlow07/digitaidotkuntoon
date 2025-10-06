<?php
// Apufunktiot DigiTaidot Kuntoon! -sivustolle
require_once 'auth.php';

class Functions {
    private $pdo;
    private $auth;

    public function __construct($pdo, $auth) {
        $this->pdo = $pdo;
        $this->auth = $auth;
    }

    // Siivoa käyttäjän syöttö
    public function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    // Tarkista sähköpostin muoto
    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Tarkista salasanan vahvuus
    public function validatePassword($password) {
        return strlen($password) >= 6;
    }

    // Hae kaikki kurssit
    public function getAllCourses() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM courses ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Kurssien hakuvirhe: " . $e->getMessage());
            return [];
        }
    }

    // Hae kurssi ID:n perusteella
    public function getCourse($course_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            return $stmt->fetch();
        } catch(PDOException $e) {
            error_log("Kurssin hakuvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Hae kurssin moduulit
    public function getCourseModules($course_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM modules WHERE course_id = ? ORDER BY order_index ASC");
            $stmt->execute([$course_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Moduulien hakuvirhe: " . $e->getMessage());
            return [];
        }
    }

    // Hae kurssin kokeet
    public function getCourseQuizzes($course_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY id ASC");
            $stmt->execute([$course_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Kokeiden hakuvirhe: " . $e->getMessage());
            return [];
        }
    }

    // Lisää uusi kurssi
    public function addCourse($title, $description, $category, $difficulty, $age_group) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO courses (title, description, category, difficulty, age_group) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $category, $difficulty, $age_group]);
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            error_log("Kurssin lisäysvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Päivitä kurssi
    public function updateCourse($course_id, $title, $description, $category, $difficulty, $age_group) {
        try {
            $stmt = $this->pdo->prepare("UPDATE courses SET title = ?, description = ?, category = ?, difficulty = ?, age_group = ? WHERE id = ?");
            return $stmt->execute([$title, $description, $category, $difficulty, $age_group, $course_id]);
        } catch(PDOException $e) {
            error_log("Kurssin päivitysvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Poista kurssi
    public function deleteCourse($course_id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM courses WHERE id = ?");
            return $stmt->execute([$course_id]);
        } catch(PDOException $e) {
            error_log("Kurssin poistovirhe: " . $e->getMessage());
            return false;
        }
    }

    // Lisää moduuli kurssiin
    public function addModule($course_id, $title, $content, $order_index = 0) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO modules (course_id, title, content, order_index) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $title, $content, $order_index]);
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            error_log("Moduulin lisäysvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Lisää koe kurssiin
    public function addQuiz($course_id, $question, $correct_answer, $options) {
        try {
            $options_json = json_encode($options);
            $stmt = $this->pdo->prepare("INSERT INTO quizzes (course_id, question, correct_answer, options) VALUES (?, ?, ?, ?)");
            $stmt->execute([$course_id, $question, $correct_answer, $options_json]);
            return $this->pdo->lastInsertId();
        } catch(PDOException $e) {
            error_log("Kokeen lisäysvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Tallenna kokeen tulos
    public function saveQuizResult($user_id, $quiz_id, $score) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO quiz_results (user_id, quiz_id, score) VALUES (?, ?, ?)");
            return $stmt->execute([$user_id, $quiz_id, $score]);
        } catch(PDOException $e) {
            error_log("Kokeen tuloksen tallennusvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Hae käyttäjän kokeiden tulokset
    public function getUserQuizResults($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT qr.*, q.question, c.title as course_title 
                FROM quiz_results qr 
                JOIN quizzes q ON qr.quiz_id = q.id 
                JOIN courses c ON q.course_id = c.id 
                WHERE qr.user_id = ? 
                ORDER BY qr.taken_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll();
        } catch(PDOException $e) {
            error_log("Kokeiden tulosten hakuvirhe: " . $e->getMessage());
            return [];
        }
    }

    // Tarkista, onko käyttäjä tehnyt kokeen
    public function hasUserTakenQuiz($user_id, $quiz_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM quiz_results WHERE user_id = ? AND quiz_id = ?");
            $stmt->execute([$user_id, $quiz_id]);
            return $stmt->fetch() !== false;
        } catch(PDOException $e) {
            error_log("Kokeen tekemisen tarkistusvirhe: " . $e->getMessage());
            return false;
        }
    }

    // Laske kokeen pistemäärä
    public function calculateQuizScore($answers, $quiz_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT correct_answer FROM quizzes WHERE id = ?");
            $stmt->execute([$quiz_id]);
            $quiz = $stmt->fetch();

            if (!$quiz) {
                return 0;
            }

            return ($answers === $quiz['correct_answer']) ? 1 : 0;
        } catch(PDOException $e) {
            error_log("Pistemäärän laskuvirhe: " . $e->getMessage());
            return 0;
        }
    }

    // Näytä virheilmoitus
    public function showError($message) {
        return "<div class='alert alert-danger' role='alert'>" . $this->sanitizeInput($message) . "</div>";
    }

    // Näytä onnistumisilmoitus
    public function showSuccess($message) {
        return "<div class='alert alert-success' role='alert'>" . $this->sanitizeInput($message) . "</div>";
    }

    // Tarkista maksuvaatimus
    public function requirePayment() {
        if (!$this->auth->hasActiveSubscription()) {
            header('Location: index.php?error=subscription_required');
            exit();
        }
    }

    // Tarkista admin-oikeudet
    public function requireAdmin() {
        if (!$this->auth->isAdmin()) {
            header('Location: dashboard.php?error=access_denied');
            exit();
        }
    }

    // Tarkista kirjautuminen
    public function requireLogin() {
        if (!$this->auth->isLoggedIn()) {
            header('Location: index.php?error=login_required');
            exit();
        }
    }

    // Muotoile päivämäärä suomeksi
    public function formatDate($date) {
        $timestamp = strtotime($date);
        return date('d.m.Y', $timestamp);
    }

    // Muotoile aikaleima suomeksi
    public function formatDateTime($datetime) {
        $timestamp = strtotime($datetime);
        return date('d.m.Y H:i', $timestamp);
    }
}

// Luodaan globaali functions-instanssi
$functions = new Functions($pdo, $auth);
?>
