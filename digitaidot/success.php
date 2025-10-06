<?php
// DigiTaidot Kuntoon! - PayPal-maksun onnistuminen
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Maksu onnistui';

// Tarkista kirjautuminen
$functions->requireLogin();

// Hae subscription ID URL:sta
$subscription_id = $_GET['subscriptionID'] ?? '';

if (empty($subscription_id)) {
    header('Location: dashboard.php?error=invalid_subscription');
    exit();
}

// Aktivoi käyttäjän tilaus
$success = $auth->activateSubscription($_SESSION['user_id'], $subscription_id);

if ($success) {
    // Päivitä session-tiedot
    $_SESSION['subscription_active'] = true;
    $_SESSION['subscription_end'] = date('Y-m-d', strtotime('+1 month'));
    
    $success_message = 'Tilaus aktivoitu onnistuneesti!';
} else {
    $error_message = 'Tilauksen aktivointi epäonnistui. Ota yhteyttä tukeen.';
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if (isset($success_message)): ?>
                <!-- Onnistumisviesti -->
                <div class="card border-success">
                    <div class="card-body text-center p-5">
                        <div class="success-icon mb-4">
                            <i class="bi bi-check-circle-fill display-1 text-success"></i>
                        </div>
                        
                        <h2 class="fw-bold text-success mb-3">Maksu onnistui!</h2>
                        
                        <p class="lead mb-4">
                            Kiitos tilauksestasi! Tilauksesi on nyt aktiivinen ja sinulla on pääsy 
                            kaikkiin kursseihin ja kokeisiin.
                        </p>
                        
                        <div class="alert alert-info mb-4">
                            <h5><i class="bi bi-info-circle"></i> Tilaustiedot</h5>
                            <ul class="list-unstyled mb-0">
                                <li><strong>Subscription ID:</strong> <?php echo htmlspecialchars($subscription_id); ?></li>
                                <li><strong>Tilaus aktivoitu:</strong> <?php echo $functions->formatDateTime(date('Y-m-d H:i:s')); ?></li>
                                <li><strong>Päättyy:</strong> <?php echo $functions->formatDate($_SESSION['subscription_end']); ?></li>
                            </ul>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="dashboard.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-house"></i> Siirry dashboardiin
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-person"></i> Näytä profiili
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Virheviesti -->
                <div class="card border-danger">
                    <div class="card-body text-center p-5">
                        <div class="error-icon mb-4">
                            <i class="bi bi-exclamation-triangle-fill display-1 text-danger"></i>
                        </div>
                        
                        <h2 class="fw-bold text-danger mb-3">Maksuvirhe</h2>
                        
                        <p class="lead mb-4">
                            Tilauksesi maksussa tapahtui virhe. Ota yhteyttä tukeen saadaksesi apua.
                        </p>
                        
                        <div class="alert alert-warning mb-4">
                            <h5><i class="bi bi-info-circle"></i> Yhteystiedot</h5>
                            <p class="mb-0">
                                <strong>Sähköposti:</strong> arttuz311@gmail.com<br>
                                <strong>Subscription ID:</strong> <?php echo htmlspecialchars($subscription_id); ?>
                            </p>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="index.php#subscription" class="btn btn-primary btn-lg">
                                <i class="bi bi-credit-card"></i> Yritä maksua uudelleen
                            </a>
                            <a href="profile.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-person"></i> Profiili
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lisäohjeita -->
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5><i class="bi bi-lightbulb"></i> Seuraavat askeleet</h5>
                    <ol>
                        <li>Siirry dashboardiin nähdäksesi kaikki saatavilla olevat kurssit</li>
                        <li>Valitse kurssi ja aloita oppiminen omassa tahdissasi</li>
                        <li>Suorita kurssien kokeet testataksesi tietosi</li>
                        <li>Seuraa etenemistäsi profiilisivulla</li>
                    </ol>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Muistutus:</strong> Tilauksesi uusiutuu automaattisesti kuukausittain. 
                        Voit peruuttaa sen milloin tahansa profiilisivulta.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
