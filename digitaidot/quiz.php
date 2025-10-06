<?php
// DigiTaidot Kuntoon! - Kokeen suorittaminen
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Koe';

// Tarkista kirjautuminen ja tilaus
$functions->requireLogin();
$functions->requirePayment();

// Hae koe ID:n perusteella tai kurssin kokeet
$quiz_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course'] ?? 0);

if ($course_id) {
    // Näytä kurssin kaikki kokeet
    $course = $functions->getCourse($course_id);
    if (!$course) {
        header('Location: dashboard.php?error=course_not_found');
        exit();
    }
    
    $quizzes = $functions->getCourseQuizzes($course_id);
    $page_title = $course['title'] . ' - Kokeet';
} elseif ($quiz_id) {
    // Näytä yksittäinen koe
    try {
        $stmt = $pdo->prepare("
            SELECT q.*, c.title as course_title 
            FROM quizzes q 
            JOIN courses c ON q.course_id = c.id 
            WHERE q.id = ?
        ");
        $stmt->execute([$quiz_id]);
        $quiz = $stmt->fetch();
        
        if (!$quiz) {
            header('Location: dashboard.php?error=quiz_not_found');
            exit();
        }
        
        $page_title = $quiz['course_title'] . ' - Koe';
    } catch(PDOException $e) {
        error_log("Kokeen hakuvirhe: " . $e->getMessage());
        header('Location: dashboard.php?error=database_error');
        exit();
    }
} else {
    header('Location: dashboard.php?error=invalid_quiz');
    exit();
}

// Käsittele kokeen vastaus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $quiz_id) {
    $answer = $_POST['answer'] ?? '';
    
    if (!empty($answer)) {
        $score = $functions->calculateQuizScore($answer, $quiz_id);
        $functions->saveQuizResult($_SESSION['user_id'], $quiz_id, $score);
        
        $success_message = $score ? 'Oikein! Hyvä työ!' : 'Väärä vastaus. Yritä uudelleen!';
        $correct_answer = $quiz['correct_answer'];
        $options = json_decode($quiz['options'], true);
    }
}

include 'includes/header.php';
?>

<div class="container py-4">
    <?php if ($course_id && !empty($quizzes)): ?>
        <!-- Kurssin kokeiden lista -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="course.php?id=<?php echo $course_id; ?>"><?php echo htmlspecialchars($course['title']); ?></a></li>
                        <li class="breadcrumb-item active">Kokeet</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="bi bi-question-circle text-primary"></i>
                    <?php echo htmlspecialchars($course['title']); ?> - Kokeet
                </h1>
            </div>
        </div>

        <div class="row">
            <?php foreach ($quizzes as $quiz_item): ?>
                <?php 
                $has_taken = $functions->hasUserTakenQuiz($_SESSION['user_id'], $quiz_item['id']);
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 quiz-card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-question-circle text-primary"></i>
                                Koe #<?php echo $quiz_item['id']; ?>
                            </h5>
                            
                            <p class="card-text">
                                <?php echo htmlspecialchars(substr($quiz_item['question'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="quiz.php?id=<?php echo $quiz_item['id']; ?>" 
                                   class="btn <?php echo $has_taken ? 'btn-success' : 'btn-primary'; ?>">
                                    <?php if ($has_taken): ?>
                                        <i class="bi bi-check-circle"></i> Tehty
                                    <?php else: ?>
                                        <i class="bi bi-play-circle"></i> Aloita
                                    <?php endif; ?>
                                </a>
                                
                                <?php if ($has_taken): ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-trophy"></i> Suoritettu
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($quiz_id): ?>
        <!-- Yksittäinen koe -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="course.php?id=<?php echo $quiz['course_id']; ?>"><?php echo htmlspecialchars($quiz['course_title']); ?></a></li>
                        <li class="breadcrumb-item active">Koe</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">
                            <i class="bi bi-question-circle text-primary"></i>
                            <?php echo htmlspecialchars($quiz['course_title']); ?> - Koe
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($success_message)): ?>
                            <!-- Tulos -->
                            <div class="alert <?php echo $score ? 'alert-success' : 'alert-danger'; ?> mb-4">
                                <h4>
                                    <i class="bi <?php echo $score ? 'bi-check-circle' : 'bi-x-circle'; ?>"></i>
                                    <?php echo $success_message; ?>
                                </h4>
                                <p class="mb-0">
                                    <strong>Oikea vastaus:</strong> <?php echo htmlspecialchars($correct_answer); ?>
                                </p>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="quiz.php?course=<?php echo $quiz['course_id']; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Takaisin kokeisiin
                                </a>
                                <a href="course.php?id=<?php echo $quiz['course_id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-book"></i> Takaisin kurssiin
                                </a>
                            </div>

                        <?php else: ?>
                            <!-- Koe -->
                            <h4 class="mb-4"><?php echo htmlspecialchars($quiz['question']); ?></h4>

                            <form method="POST" action="">
                                <?php
                                $options = json_decode($quiz['options'], true);
                                if ($options && is_array($options)):
                                ?>
                                    <div class="mb-4">
                                        <?php foreach ($options as $option): ?>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="answer" 
                                                   id="option_<?php echo htmlspecialchars($option); ?>" 
                                                   value="<?php echo htmlspecialchars($option); ?>" required>
                                            <label class="form-check-label" for="option_<?php echo htmlspecialchars($option); ?>">
                                                <?php echo htmlspecialchars($option); ?>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="quiz.php?course=<?php echo $quiz['course_id']; ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left"></i> Takaisin
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-send"></i> Lähetä vastaus
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Tämän kokeen vaihtoehtoja ei ole määritelty oikein.
                                    </div>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle display-4 mb-3"></i>
                    <h4>Ei kokeita saatavilla</h4>
                    <p class="mb-0">Tämän kurssin kokeet lisätään pian.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
