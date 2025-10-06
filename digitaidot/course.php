<?php
// DigiTaidot Kuntoon! - Yksittäinen kurssi
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Kurssi';

// Tarkista kirjautuminen ja tilaus
$functions->requireLogin();
$functions->requirePayment();

// Hae kurssi ID:n perusteella
$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
    header('Location: dashboard.php?error=invalid_course');
    exit();
}

$course = $functions->getCourse($course_id);
if (!$course) {
    header('Location: dashboard.php?error=course_not_found');
    exit();
}

$modules = $functions->getCourseModules($course_id);
$quizzes = $functions->getCourseQuizzes($course_id);

$page_title = $course['title'];

include 'includes/header.php';
?>

<div class="container py-4">
    <!-- Kurssin otsikko -->
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['title']); ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Kurssin tiedot -->
            <div class="card mb-4">
                <div class="card-body">
                    <h1 class="card-title">
                        <i class="bi bi-book text-primary"></i>
                        <?php echo htmlspecialchars($course['title']); ?>
                    </h1>
                    
                    <p class="card-text lead">
                        <?php echo htmlspecialchars($course['description']); ?>
                    </p>
                    
                    <div class="course-meta mb-3">
                        <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($course['category']); ?></span>
                        <span class="badge bg-secondary fs-6"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                        <span class="badge bg-info fs-6"><?php echo htmlspecialchars($course['age_group']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Kurssin moduulit -->
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-list-ol"></i> Kurssin moduulit
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (empty($modules)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            Tämän kurssin moduulit lisätään pian. Pysy kuulolla!
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="modulesAccordion">
                            <?php foreach ($modules as $index => $module): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $module['id']; ?>">
                                    <button class="accordion-button <?php echo $index === 0 ? '' : 'collapsed'; ?>" 
                                            type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#collapse<?php echo $module['id']; ?>">
                                        <i class="bi bi-play-circle me-2"></i>
                                        <?php echo htmlspecialchars($module['title']); ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $module['id']; ?>" 
                                     class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>"
                                     data-bs-parent="#modulesAccordion">
                                    <div class="accordion-body">
                                        <div class="module-content">
                                            <?php echo nl2br(htmlspecialchars($module['content'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Kurssin eteneminen -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up"></i> Kurssin eteneminen
                    </h5>
                </div>
                <div class="card-body">
                    <?php
                    $completed_quizzes = 0;
                    foreach ($quizzes as $quiz) {
                        if ($functions->hasUserTakenQuiz($_SESSION['user_id'], $quiz['id'])) {
                            $completed_quizzes++;
                        }
                    }
                    
                    $total_quizzes = count($quizzes);
                    $progress_percentage = $total_quizzes > 0 ? ($completed_quizzes / $total_quizzes) * 100 : 0;
                    ?>
                    
                    <div class="progress mb-3" style="height: 20px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $progress_percentage; ?>%">
                            <?php echo round($progress_percentage); ?>%
                        </div>
                    </div>
                    
                    <p class="mb-0">
                        <strong><?php echo $completed_quizzes; ?></strong> / 
                        <strong><?php echo $total_quizzes; ?></strong> kokeita suoritettu
                    </p>
                </div>
            </div>

            <!-- Kokeet -->
            <?php if (!empty($quizzes)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-question-circle"></i> Kurssin kokeet
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php foreach ($quizzes as $quiz): ?>
                            <?php 
                            $has_taken = $functions->hasUserTakenQuiz($_SESSION['user_id'], $quiz['id']);
                            ?>
                            <a href="quiz.php?id=<?php echo $quiz['id']; ?>" 
                               class="btn <?php echo $has_taken ? 'btn-success' : 'btn-outline-primary'; ?>">
                                <?php if ($has_taken): ?>
                                    <i class="bi bi-check-circle"></i> Tehty
                                <?php else: ?>
                                    <i class="bi bi-play-circle"></i> Tee koe
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Navigointi -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Takaisin dashboardiin
                        </a>
                        
                        <?php if (!empty($quizzes)): ?>
                            <a href="quiz.php?course=<?php echo $course_id; ?>" class="btn btn-primary">
                                <i class="bi bi-question-circle"></i> Aloita kokeet
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
