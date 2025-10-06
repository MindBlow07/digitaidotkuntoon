<?php
// DigiTaidot Kuntoon! - Käyttäjän profiili
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Profiili';

// Tarkista kirjautuminen
$functions->requireLogin();

// Hae käyttäjän tiedot
$user = $auth->getUser($_SESSION['user_id']);
$quiz_results = $functions->getUserQuizResults($_SESSION['user_id']);

include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="fw-bold">
                <i class="bi bi-person-circle text-primary"></i> Oma profiili
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Käyttäjätiedot -->
            <div class="card">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-person-circle display-1 text-primary"></i>
                        <h3 class="mt-3"><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        
                        <?php if ($auth->isAdmin()): ?>
                            <span class="badge bg-warning text-dark fs-6">
                                <i class="bi bi-shield-check"></i> Admin
                            </span>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="user-stats">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tili luotu:</span>
                            <strong><?php echo $functions->formatDate($user['created_at']); ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Rooli:</span>
                            <strong><?php echo $user['role']; ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tilauksen tila:</span>
                            <?php if ($auth->hasActiveSubscription()): ?>
                                <span class="badge bg-success">Aktiivinen</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Ei tilausta</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($user['subscription_end']): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tilaus päättyy:</span>
                            <strong><?php echo $functions->formatDate($user['subscription_end']); ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Tilaus- ja maksutiedot -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card"></i> Tilaus- ja maksutiedot
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($auth->hasActiveSubscription()): ?>
                        <div class="alert alert-success">
                            <h5><i class="bi bi-check-circle"></i> Aktiivinen tilaus</h5>
                            <p class="mb-0">
                                Tilauksesi on aktiivinen ja päättyy 
                                <strong><?php echo $functions->formatDate($user['subscription_end']); ?></strong>.
                                Sinulla on pääsy kaikkiin kursseihin ja kokeisiin.
                            </p>
                        </div>
                        
                        <div class="subscription-info">
                            <h6>Tilaus:</h6>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-check text-success"></i> Pääsy kaikkiin kursseihin</li>
                                <li><i class="bi bi-check text-success"></i> Interaktiiviset kokeet ja testit</li>
                                <li><i class="bi bi-check text-success"></i> 24/7 pääsy sisältöön</li>
                                <li><i class="bi bi-check text-success"></i> Todistukset kurssien suorittamisesta</li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <h5><i class="bi bi-exclamation-triangle"></i> Ei aktiivista tilausta</h5>
                            <p class="mb-0">
                                Sinulla ei ole aktiivista tilausta. Tilaa palvelu saadaksesi pääsyn kursseihin ja kokeisiin.
                            </p>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <a href="index.php#subscription" class="btn btn-primary">
                                <i class="bi bi-credit-card"></i> Tilaa palvelu nyt
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Oppimistilastot -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up"></i> Oppimistilastot
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($quiz_results)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            Et ole vielä suorittanut yhtään kokeita. Aloita oppiminen valitsemalla kurssi dashboardista!
                        </div>
                    <?php else: ?>
                        <div class="row mb-4">
                            <div class="col-md-4 text-center">
                                <div class="stat-item">
                                    <h3 class="fw-bold text-primary"><?php echo count($quiz_results); ?></h3>
                                    <p class="text-muted mb-0">Suoritettua kokeita</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="stat-item">
                                    <h3 class="fw-bold text-success"><?php echo array_sum(array_column($quiz_results, 'score')); ?></h3>
                                    <p class="text-muted mb-0">Kokonaispisteet</p>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="stat-item">
                                    <h3 class="fw-bold text-info">
                                        <?php 
                                        $total_questions = count($quiz_results);
                                        $correct_answers = array_sum(array_column($quiz_results, 'score'));
                                        $percentage = $total_questions > 0 ? round(($correct_answers / $total_questions) * 100) : 0;
                                        echo $percentage . '%';
                                        ?>
                                    </h3>
                                    <p class="text-muted mb-0">Onnistumisprosentti</p>
                                </div>
                            </div>
                        </div>

                        <!-- Viimeisimmät tulokset -->
                        <h6>Viimeisimmät tulokset:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Kurssi</th>
                                        <th>Kysymys</th>
                                        <th>Tulos</th>
                                        <th>Päivämäärä</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($quiz_results, 0, 10) as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['course_title']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($result['question'], 0, 30)) . '...'; ?>
                                        </td>
                                        <td>
                                            <?php if ($result['score'] == 1): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check"></i> Oikein
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-x"></i> Väärin
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small><?php echo $functions->formatDate($result['taken_at']); ?></small>
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

<?php include 'includes/footer.php'; ?>
