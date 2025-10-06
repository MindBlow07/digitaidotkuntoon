<?php
// DigiTaidot Kuntoon! - Käyttäjän kirjautuminen
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Kirjaudu sisään';
$error_message = '';

// Jos käyttäjä on jo kirjautunut, ohjaa dashboardiin
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Käsittele kirjautumislomake
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $functions->sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Anna sähköpostiosoite ja salasana.';
    } else {
        if ($auth->login($email, $password)) {
            // Kirjautuminen onnistui
            header('Location: dashboard.php?success=logged_in');
            exit();
        } else {
            $error_message = 'Väärä sähköpostiosoite tai salasana.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-box-arrow-in-right display-4 text-primary"></i>
                        <h2 class="fw-bold mt-3">Kirjaudu sisään</h2>
                        <p class="text-muted">Tervetuloa takaisin DigiTaidot Kuntoon! -palveluun</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Sähköpostiosoite</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Salasana</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Kirjaudu sisään
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">Eikö sinulla ole tiliä? 
                            <a href="register.php" class="text-decoration-none">Rekisteröidy tässä</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Testitilin tiedot -->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-info">
                <h5><i class="bi bi-info-circle"></i> Testitilin tiedot</h5>
                <p class="mb-2"><strong>Admin-tili:</strong></p>
                <ul class="mb-0">
                    <li><strong>Sähköposti:</strong> arttuz311@gmail.com</li>
                    <li><strong>Salasana:</strong> admin123</li>
                    <li><strong>Oikeudet:</strong> Admin-pääsy, ei tilausta vaadita</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Etusivulle palaaminen -->
<div class="container">
    <div class="row">
        <div class="col-12 text-center">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Takaisin etusivulle
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
