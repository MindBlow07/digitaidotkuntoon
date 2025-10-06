<?php
// DigiTaidot Kuntoon! - Käyttäjän dashboard
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Dashboard';

// Tarkista kirjautuminen
$functions->requireLogin();

// Jos käyttäjällä ei ole aktiivista tilausta, ohjaa etusivulle
if (!$auth->hasActiveSubscription()) {
    header('Location: index.php?error=subscription_required');
    exit();
}

// Hae käyttäjän tiedot ja kurssit
$user = $auth->getUser($_SESSION['user_id']);
$courses = $functions->getAllCourses();
$quiz_results = $functions->getUserQuizResults($_SESSION['user_id']);

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Tervetuloa-osio -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-card bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="fw-bold mb-2">
                            <i class="bi bi-house-door"></i> 
                            Tervetuloa takaisin, <?php echo htmlspecialchars($user['name']); ?>!
                        </h2>
                        <p class="mb-0">
                            Jatka digitaalisten taitojesi oppimista. Tilauksesi on voimassa 
                            <?php echo $functions->formatDate($user['subscription_end']); ?> asti.
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="subscription-status">
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-check-circle"></i> Aktiivinen tilaus
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tilastot -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-book display-6 text-primary mb-2"></i>
                    <h4 class="fw-bold"><?php echo count($courses); ?></h4>
                    <p class="text-muted mb-0">Saatavilla olevia kursseja</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-patch-check display-6 text-success mb-2"></i>
                    <h4 class="fw-bold"><?php echo count($quiz_results); ?></h4>
                    <p class="text-muted mb-0">Suoritettua kokeita</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-trophy display-6 text-warning mb-2"></i>
                    <h4 class="fw-bold">
                        <?php 
                        $total_score = array_sum(array_column($quiz_results, 'score'));
                        echo $total_score;
                        ?>
                    </h4>
                    <p class="text-muted mb-0">Kokonaispisteet</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-calendar-check display-6 text-info mb-2"></i>
                    <h4 class="fw-bold">
                        <?php echo $functions->formatDate($user['subscription_end']); ?>
                    </h4>
                    <p class="text-muted mb-0">Tilaus päättyy</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kurssit -->
    <div class="row">
        <div class="col-12">
            <h3 class="fw-bold mb-4">
                <i class="bi bi-book"></i> Omat kurssit
            </h3>
        </div>
    </div>

    <div class="row">
        <?php if (empty($courses)): ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle display-4 mb-3"></i>
                    <h4>Ei kursseja vielä saatavilla</h4>
                    <p class="mb-0">Kurssit lisätään pian. Pysy kuulolla!</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($courses as $course): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 course-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-book text-primary"></i>
                            <?php echo htmlspecialchars($course['title']); ?>
                        </h5>
                        
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?>
                        </p>
                        
                        <div class="course-meta mb-3">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($course['category']); ?></span>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                            <span class="badge bg-info"><?php echo htmlspecialchars($course['age_group']); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-play-circle"></i> Aloita kurssi
                            </a>
                            
                            <?php
                            // Tarkista, onko käyttäjä tehnyt kurssin kokeen
                            $course_quizzes = $functions->getCourseQuizzes($course['id']);
                            $completed_quizzes = 0;
                            foreach ($course_quizzes as $quiz) {
                                if ($functions->hasUserTakenQuiz($_SESSION['user_id'], $quiz['id'])) {
                                    $completed_quizzes++;
                                }
                            }
                            ?>
                            
                            <?php if (!empty($course_quizzes)): ?>
                                <span class="text-muted small">
                                    <?php echo $completed_quizzes; ?>/<?php echo count($course_quizzes); ?> kokeita tehty
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Viimeisimmät tulokset -->
    <?php if (!empty($quiz_results)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h3 class="fw-bold mb-4">
                <i class="bi bi-trophy"></i> Viimeisimmät tulokset
            </h3>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kurssi</th>
                                    <th>Kysymys</th>
                                    <th>Tulos</th>
                                    <th>Suoritettu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($quiz_results, 0, 5) as $result): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($result['course_title']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(substr($result['question'], 0, 50)) . '...'; ?>
                                    </td>
                                    <td>
                                        <?php if ($result['score'] == 1): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle"></i> Oikein
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-x-circle"></i> Väärin
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo $functions->formatDateTime($result['taken_at']); ?>
                                        </small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
