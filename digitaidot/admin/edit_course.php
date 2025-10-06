<?php
// DigiTaidot Kuntoon! - Kurssin muokkaus
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$page_title = 'Muokkaa kurssia';

// Tarkista admin-oikeudet
$functions->requireAdmin();

$error_message = '';
$success_message = '';

// Hae kurssi ID:n perusteella
$course_id = (int)($_GET['id'] ?? 0);
if (!$course_id) {
    header('Location: index.php?error=invalid_course');
    exit();
}

$course = $functions->getCourse($course_id);
if (!$course) {
    header('Location: index.php?error=course_not_found');
    exit();
}

// Käsittele kurssin päivityslomake
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $functions->sanitizeInput($_POST['title'] ?? '');
    $description = $functions->sanitizeInput($_POST['description'] ?? '');
    $category = $functions->sanitizeInput($_POST['category'] ?? '');
    $difficulty = $functions->sanitizeInput($_POST['difficulty'] ?? '');
    $age_group = $functions->sanitizeInput($_POST['age_group'] ?? '');

    // Validointi
    if (empty($title)) {
        $error_message = 'Kurssin otsikko on pakollinen.';
    } elseif (empty($description)) {
        $error_message = 'Kurssin kuvaus on pakollinen.';
    } elseif (empty($category)) {
        $error_message = 'Kategoria on pakollinen.';
    } elseif (empty($difficulty)) {
        $error_message = 'Vaikeustaso on pakollinen.';
    } elseif (empty($age_group)) {
        $error_message = 'Ikäryhmä on pakollinen.';
    } else {
        $success = $functions->updateCourse($course_id, $title, $description, $category, $difficulty, $age_group);
        
        if ($success) {
            header('Location: index.php?success=course_updated');
            exit();
        } else {
            $error_message = 'Kurssin päivitys epäonnistui. Yritä uudelleen.';
        }
    }
} else {
    // Lataa kurssin tiedot lomakkeeseen
    $title = $course['title'];
    $description = $course['description'];
    $category = $course['category'];
    $difficulty = $course['difficulty'];
    $age_group = $course['age_group'];
}

include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Admin-paneeli</a></li>
                    <li class="breadcrumb-item active">Muokkaa kurssia</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-pencil text-primary"></i> Muokkaa kurssia
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="title" class="form-label">Kurssin otsikko *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Kurssin kuvaus *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                            <div class="form-text">Kuvaa kurssin sisältöä ja oppimistavoitteita.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Kategoria *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Valitse kategoria</option>
                                    <option value="Perusteet" <?php echo ($category === 'Perusteet') ? 'selected' : ''; ?>>Perusteet</option>
                                    <option value="Kommunikaatio" <?php echo ($category === 'Kommunikaatio') ? 'selected' : ''; ?>>Kommunikaatio</option>
                                    <option value="Tuottavuus" <?php echo ($category === 'Tuottavuus') ? 'selected' : ''; ?>>Tuottavuus</option>
                                    <option value="Turvallisuus" <?php echo ($category === 'Turvallisuus') ? 'selected' : ''; ?>>Turvallisuus</option>
                                    <option value="Verkko" <?php echo ($category === 'Verkko') ? 'selected' : ''; ?>>Verkko</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="difficulty" class="form-label">Vaikeustaso *</label>
                                <select class="form-select" id="difficulty" name="difficulty" required>
                                    <option value="">Valitse vaikeustaso</option>
                                    <option value="Aloittelija" <?php echo ($difficulty === 'Aloittelija') ? 'selected' : ''; ?>>Aloittelija</option>
                                    <option value="Keskitaso" <?php echo ($difficulty === 'Keskitaso') ? 'selected' : ''; ?>>Keskitaso</option>
                                    <option value="Edistynyt" <?php echo ($difficulty === 'Edistynyt') ? 'selected' : ''; ?>>Edistynyt</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="age_group" class="form-label">Ikäryhmä *</label>
                                <select class="form-select" id="age_group" name="age_group" required>
                                    <option value="">Valitse ikäryhmä</option>
                                    <option value="Kaikki ikäryhmät" <?php echo ($age_group === 'Kaikki ikäryhmät') ? 'selected' : ''; ?>>Kaikki ikäryhmät</option>
                                    <option value="Lapset (7-12v)" <?php echo ($age_group === 'Lapset (7-12v)') ? 'selected' : ''; ?>>Lapset (7-12v)</option>
                                    <option value="Nuoret (13-18v)" <?php echo ($age_group === 'Nuoret (13-18v)') ? 'selected' : ''; ?>>Nuoret (13-18v)</option>
                                    <option value="Aikuiset (18v+)" <?php echo ($age_group === 'Aikuiset (18v+)') ? 'selected' : ''; ?>>Aikuiset (18v+)</option>
                                    <option value="Ikääntyneet (65v+)" <?php echo ($age_group === 'Ikääntyneet (65v+)') ? 'selected' : ''; ?>>Ikääntyneet (65v+)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Peruuta
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Päivitä kurssi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Kurssin tiedot -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Kurssin tiedot</h5>
                </div>
                <div class="card-body">
                    <p><strong>ID:</strong> <?php echo $course['id']; ?></p>
                    <p><strong>Luotu:</strong> <?php echo $functions->formatDate($course['created_at']); ?></p>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <a href="../course.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-eye"></i> Esikatsele kurssi
                        </a>
                        
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash"></i> Poista kurssi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kurssin moduulit ja kokeet -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list"></i> Kurssin sisältö</h5>
                </div>
                <div class="card-body">
                    <?php
                    $modules = $functions->getCourseModules($course_id);
                    $quizzes = $functions->getCourseQuizzes($course_id);
                    ?>
                    
                    <p><strong>Moduuleja:</strong> <?php echo count($modules); ?></p>
                    <p><strong>Kokeita:</strong> <?php echo count($quizzes); ?></p>
                    
                    <div class="d-grid gap-2 mt-3">
                        <a href="quizzes.php?course=<?php echo $course_id; ?>" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-question-circle"></i> Hallinnoi kokeita
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Poiston vahvistus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vahvista poisto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Haluatko varmasti poistaa kurssin "<?php echo htmlspecialchars($course['title']); ?>"?</p>
                <p class="text-danger"><strong>Varoitus:</strong> Tämä toiminto poistaa myös kaikki kurssiin liittyvät moduulit ja kokeet!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Peruuta</button>
                <form method="POST" action="delete_course.php" style="display: inline;">
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Poista kurssi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
