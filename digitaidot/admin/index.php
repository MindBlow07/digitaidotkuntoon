<?php
// DigiTaidot Kuntoon! - Admin-paneeli
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$page_title = 'Admin-paneeli';

// Tarkista admin-oikeudet
$functions->requireAdmin();

// Hae tilastot
$stats = [];

// Käyttäjät
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$stats['users'] = $stmt->fetch()['total_users'];

// Aktiiviset tilaajat
$stmt = $pdo->prepare("SELECT COUNT(*) as active_subscribers FROM users WHERE subscription_active = 1");
$stmt->execute();
$stats['subscribers'] = $stmt->fetch()['active_subscribers'];

// Kurssit
$stmt = $pdo->prepare("SELECT COUNT(*) as total_courses FROM courses");
$stmt->execute();
$stats['courses'] = $stmt->fetch()['total_courses'];

// Kokeet
$stmt = $pdo->prepare("SELECT COUNT(*) as total_quizzes FROM quizzes");
$stmt->execute();
$stats['quizzes'] = $stmt->fetch()['total_quizzes'];

// Viimeisimmät käyttäjät
$stmt = $pdo->prepare("SELECT name, email, role, subscription_active, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->fetchAll();

// Viimeisimmät kurssit
$stmt = $pdo->prepare("SELECT title, category, created_at FROM courses ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recent_courses = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container py-4">
    <!-- Admin-tervehdys -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="admin-header bg-primary text-white p-4 rounded">
                <h1 class="fw-bold mb-2">
                    <i class="bi bi-gear"></i> Admin-paneeli
                </h1>
                <p class="mb-0">Tervetuloa hallintapaneeliin, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
            </div>
        </div>
    </div>

    <!-- Tilastot -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-people display-6 text-primary mb-2"></i>
                    <h3 class="fw-bold"><?php echo $stats['users']; ?></h3>
                    <p class="text-muted mb-0">Käyttäjää</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-person-check display-6 text-success mb-2"></i>
                    <h3 class="fw-bold"><?php echo $stats['subscribers']; ?></h3>
                    <p class="text-muted mb-0">Aktiivista tilaajaa</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-book display-6 text-info mb-2"></i>
                    <h3 class="fw-bold"><?php echo $stats['courses']; ?></h3>
                    <p class="text-muted mb-0">Kurssia</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-question-circle display-6 text-warning mb-2"></i>
                    <h3 class="fw-bold"><?php echo $stats['quizzes']; ?></h3>
                    <p class="text-muted mb-0">Kokeita</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pikavalikko -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Pikavalikko</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="add_course.php" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> Lisää kurssi
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="quizzes.php" class="btn btn-success w-100">
                                <i class="bi bi-question-circle"></i> Hallinnoi kokeita
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="users.php" class="btn btn-info w-100">
                                <i class="bi bi-people"></i> Käyttäjähallinta
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="../dashboard.php" class="btn btn-outline-secondary w-100">
                                <i class="bi bi-house"></i> Takaisin sivustolle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Viimeisimmät käyttäjät -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> Viimeisimmät käyttäjät
                        <a href="users.php" class="btn btn-sm btn-outline-primary float-end">Näytä kaikki</a>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_users)): ?>
                        <p class="text-muted">Ei käyttäjiä vielä.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nimi</th>
                                        <th>Rooli</th>
                                        <th>Tilaus</th>
                                        <th>Rekisteröitynyt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] === 'ADMIN'): ?>
                                                <span class="badge bg-warning text-dark">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Käyttäjä</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['subscription_active']): ?>
                                                <span class="badge bg-success">Aktiivinen</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Ei tilausta</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo $functions->formatDate($user['created_at']); ?></small>
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

        <!-- Viimeisimmät kurssit -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-book"></i> Viimeisimmät kurssit
                        <a href="add_course.php" class="btn btn-sm btn-outline-primary float-end">Lisää kurssi</a>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_courses)): ?>
                        <p class="text-muted">Ei kursseja vielä.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Otsikko</th>
                                        <th>Kategoria</th>
                                        <th>Luotu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_courses as $course): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($course['category']); ?></span>
                                        </td>
                                        <td>
                                            <small><?php echo $functions->formatDate($course['created_at']); ?></small>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>
