<?php
// DigiTaidot Kuntoon! - Kokeiden hallinta
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$page_title = 'Kokeiden hallinta';

// Tarkista admin-oikeudet
$functions->requireAdmin();

$error_message = '';
$success_message = '';

// Hae kurssi ID (valinnainen)
$course_id = (int)($_GET['course'] ?? 0);

// Hae kurssit
$courses = $functions->getAllCourses();

// Hae kokeet
if ($course_id) {
    $quizzes = $functions->getCourseQuizzes($course_id);
    $course = $functions->getCourse($course_id);
} else {
    // Hae kaikki kokeet
    try {
        $stmt = $pdo->prepare("
            SELECT q.*, c.title as course_title 
            FROM quizzes q 
            JOIN courses c ON q.course_id = c.id 
            ORDER BY q.course_id, q.id
        ");
        $stmt->execute();
        $quizzes = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Kokeiden hakuvirhe: " . $e->getMessage());
        $quizzes = [];
    }
}

// Käsittele kokeen lisäämislomake
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_quiz'])) {
    $quiz_course_id = (int)($_POST['course_id'] ?? 0);
    $question = $functions->sanitizeInput($_POST['question'] ?? '');
    $correct_answer = $functions->sanitizeInput($_POST['correct_answer'] ?? '');
    $options = [];
    
    // Kerää vaihtoehdot
    for ($i = 1; $i <= 4; $i++) {
        $option = $functions->sanitizeInput($_POST["option_$i"] ?? '');
        if (!empty($option)) {
            $options[] = $option;
        }
    }

    // Validointi
    if (empty($quiz_course_id)) {
        $error_message = 'Valitse kurssi.';
    } elseif (empty($question)) {
        $error_message = 'Kysymys on pakollinen.';
    } elseif (empty($correct_answer)) {
        $error_message = 'Oikea vastaus on pakollinen.';
    } elseif (count($options) < 2) {
        $error_message = 'Tarvitset vähintään 2 vaihtoehtoa.';
    } elseif (!in_array($correct_answer, $options)) {
        $error_message = 'Oikea vastaus täytyy olla vaihtoehtojen joukossa.';
    } else {
        $quiz_id = $functions->addQuiz($quiz_course_id, $question, $correct_answer, $options);
        
        if ($quiz_id) {
            $success_message = 'Koe lisätty onnistuneesti!';
            // Päivitä sivun tiedot
            if ($course_id) {
                $quizzes = $functions->getCourseQuizzes($course_id);
            }
        } else {
            $error_message = 'Kokeen lisääminen epäonnistui. Yritä uudelleen.';
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin-paneeli</a></li>
                    <li class="breadcrumb-item active">Kokeiden hallinta</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-question-circle text-primary"></i> Kokeiden hallinta
                    <?php if ($course_id && isset($course)): ?>
                        - <?php echo htmlspecialchars($course['title']); ?>
                    <?php endif; ?>
                </h2>
                
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                    <i class="bi bi-plus-circle"></i> Lisää koe
                </button>
            </div>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <!-- Kurssivalikko -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>Valitse kurssi:</h5>
                    <div class="btn-group" role="group">
                        <a href="quizzes.php" class="btn <?php echo !$course_id ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            Kaikki kokeet
                        </a>
                        <?php foreach ($courses as $course_item): ?>
                            <a href="quizzes.php?course=<?php echo $course_item['id']; ?>" 
                               class="btn <?php echo $course_id == $course_item['id'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <?php echo htmlspecialchars($course_item['title']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kokeiden lista -->
    <div class="row">
        <div class="col-12">
            <?php if (empty($quizzes)): ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle display-4 mb-3"></i>
                    <h4>Ei kokeita</h4>
                    <p class="mb-0">
                        <?php if ($course_id): ?>
                            Tällä kurssilla ei ole vielä kokeita.
                        <?php else: ?>
                            Ei kokeita vielä luotu.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kysymys</th>
                                <th>Oikea vastaus</th>
                                <th>Vaihtoehdot</th>
                                <?php if (!$course_id): ?>
                                    <th>Kurssi</th>
                                <?php endif; ?>
                                <th>Toiminnot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quizzes as $quiz): ?>
                            <tr>
                                <td><?php echo $quiz['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars(substr($quiz['question'], 0, 100)); ?>
                                    <?php if (strlen($quiz['question']) > 100): ?>...<?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?php echo htmlspecialchars($quiz['correct_answer']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $options = json_decode($quiz['options'], true);
                                    if ($options && is_array($options)):
                                        foreach ($options as $option):
                                    ?>
                                        <span class="badge bg-light text-dark me-1"><?php echo htmlspecialchars($option); ?></span>
                                    <?php 
                                        endforeach;
                                    endif;
                                    ?>
                                </td>
                                <?php if (!$course_id): ?>
                                    <td><?php echo htmlspecialchars($quiz['course_title']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteQuiz(<?php echo $quiz['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lisää koe -modal -->
<div class="modal fade" id="addQuizModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Lisää uusi koe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Kurssi *</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Valitse kurssi</option>
                            <?php foreach ($courses as $course_item): ?>
                                <option value="<?php echo $course_item['id']; ?>" 
                                        <?php echo ($course_id == $course_item['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course_item['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="question" class="form-label">Kysymys *</label>
                        <textarea class="form-control" id="question" name="question" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Vaihtoehdot *</label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" name="option_1" placeholder="Vaihtoehto 1" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" name="option_2" placeholder="Vaihtoehto 2" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" name="option_3" placeholder="Vaihtoehto 3">
                            </div>
                            <div class="col-md-6 mb-2">
                                <input type="text" class="form-control" name="option_4" placeholder="Vaihtoehto 4">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correct_answer" class="form-label">Oikea vastaus *</label>
                        <input type="text" class="form-control" id="correct_answer" name="correct_answer" 
                               placeholder="Kirjoita täsmälleen sama teksti kuin yhdessä vaihtoehdoista" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Peruuta</button>
                    <button type="submit" name="add_quiz" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Lisää koe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteQuiz(quizId) {
    if (confirm('Haluatko varmasti poistaa tämän kokeen?')) {
        // Tässä voit lisätä AJAX-kutsun tai lomakkeen poistamiseen
        window.location.href = 'delete_quiz.php?id=' + quizId;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
