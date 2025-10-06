<?php
// DigiTaidot Kuntoon! - Kurssin lisääminen
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$page_title = 'Lisää kurssi';

// Tarkista admin-oikeudet
$functions->requireAdmin();

$error_message = '';
$success_message = '';

// Käsittele kurssin lisäämislomake
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
        $course_id = $functions->addCourse($title, $description, $category, $difficulty, $age_group);
        
        if ($course_id) {
            header('Location: index.php?success=course_added');
            exit();
        } else {
            $error_message = 'Kurssin lisääminen epäonnistui. Yritä uudelleen.';
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
                    <li class="breadcrumb-item active">Lisää kurssi</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">
                        <i class="bi bi-plus-circle text-primary"></i> Lisää uusi kurssi
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
                                   value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Kurssin kuvaus *</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            <div class="form-text">Kuvaa kurssin sisältöä ja oppimistavoitteita.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Kategoria *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Valitse kategoria</option>
                                    <option value="Perusteet" <?php echo (isset($category) && $category === 'Perusteet') ? 'selected' : ''; ?>>Perusteet</option>
                                    <option value="Kommunikaatio" <?php echo (isset($category) && $category === 'Kommunikaatio') ? 'selected' : ''; ?>>Kommunikaatio</option>
                                    <option value="Tuottavuus" <?php echo (isset($category) && $category === 'Tuottavuus') ? 'selected' : ''; ?>>Tuottavuus</option>
                                    <option value="Turvallisuus" <?php echo (isset($category) && $category === 'Turvallisuus') ? 'selected' : ''; ?>>Turvallisuus</option>
                                    <option value="Verkko" <?php echo (isset($category) && $category === 'Verkko') ? 'selected' : ''; ?>>Verkko</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="difficulty" class="form-label">Vaikeustaso *</label>
                                <select class="form-select" id="difficulty" name="difficulty" required>
                                    <option value="">Valitse vaikeustaso</option>
                                    <option value="Aloittelija" <?php echo (isset($difficulty) && $difficulty === 'Aloittelija') ? 'selected' : ''; ?>>Aloittelija</option>
                                    <option value="Keskitaso" <?php echo (isset($difficulty) && $difficulty === 'Keskitaso') ? 'selected' : ''; ?>>Keskitaso</option>
                                    <option value="Edistynyt" <?php echo (isset($difficulty) && $difficulty === 'Edistynyt') ? 'selected' : ''; ?>>Edistynyt</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="age_group" class="form-label">Ikäryhmä *</label>
                                <select class="form-select" id="age_group" name="age_group" required>
                                    <option value="">Valitse ikäryhmä</option>
                                    <option value="Kaikki ikäryhmät" <?php echo (isset($age_group) && $age_group === 'Kaikki ikäryhmät') ? 'selected' : ''; ?>>Kaikki ikäryhmät</option>
                                    <option value="Lapset (7-12v)" <?php echo (isset($age_group) && $age_group === 'Lapset (7-12v)') ? 'selected' : ''; ?>>Lapset (7-12v)</option>
                                    <option value="Nuoret (13-18v)" <?php echo (isset($age_group) && $age_group === 'Nuoret (13-18v)') ? 'selected' : ''; ?>>Nuoret (13-18v)</option>
                                    <option value="Aikuiset (18v+)" <?php echo (isset($age_group) && $age_group === 'Aikuiset (18v+)') ? 'selected' : ''; ?>>Aikuiset (18v+)</option>
                                    <option value="Ikääntyneet (65v+)" <?php echo (isset($age_group) && $age_group === 'Ikääntyneet (65v+)') ? 'selected' : ''; ?>>Ikääntyneet (65v+)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Peruuta
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Lisää kurssi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Ohjeita -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Ohjeita</h5>
                </div>
                <div class="card-body">
                    <h6>Kurssin otsikko:</h6>
                    <p class="small text-muted">Kirjoita selkeä ja ytimekäs otsikko, joka kuvaa kurssin sisältöä.</p>
                    
                    <h6>Kuvaus:</h6>
                    <p class="small text-muted">Kuvaa yksityiskohtaisesti mitä oppilaat oppivat kurssilla.</p>
                    
                    <h6>Kategoria:</h6>
                    <p class="small text-muted">Valitse sopiva kategoria kurssin aihealueen mukaan.</p>
                    
                    <h6>Vaikeustaso:</h6>
                    <p class="small text-muted">Määritä kurssin vaikeustaso kohderyhmän mukaan.</p>
                    
                    <h6>Ikäryhmä:</h6>
                    <p class="small text-muted">Valitse kohdeikäryhmä kurssin sisällön mukaan.</p>
                </div>
            </div>

            <!-- Seuraavat askeleet -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-arrow-right"></i> Seuraavat askeleet</h5>
                </div>
                <div class="card-body">
                    <ol class="small">
                        <li>Kurssin luomisen jälkeen voit lisätä moduuleja</li>
                        <li>Lisää kokeita testataksesi oppilaiden tietoa</li>
                        <li>Testaa kurssi ennen julkaisua</li>
                        <li>Julkaise kurssi käyttäjille</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
