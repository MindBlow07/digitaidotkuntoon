<?php
// DigiTaidot Kuntoon! - Käyttäjän rekisteröinti
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Rekisteröidy';
$error_message = '';
$success_message = '';

// Jos käyttäjä on jo kirjautunut, ohjaa dashboardiin
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Käsittele rekisteröintilomake
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $functions->sanitizeInput($_POST['name'] ?? '');
    $email = $functions->sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validointi
    if (empty($name)) {
        $error_message = 'Nimi on pakollinen.';
    } elseif (empty($email)) {
        $error_message = 'Sähköpostiosoite on pakollinen.';
    } elseif (!$functions->validateEmail($email)) {
        $error_message = 'Anna kelvollinen sähköpostiosoite.';
    } elseif (empty($password)) {
        $error_message = 'Salasana on pakollinen.';
    } elseif (!$functions->validatePassword($password)) {
        $error_message = 'Salasanan tulee olla vähintään 6 merkkiä pitkä.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Salasanat eivät täsmää.';
    } else {
        // Yritä rekisteröintiä
        $result = $auth->register($name, $email, $password);
        
        if ($result === true) {
            header('Location: index.php?success=registered');
            exit();
        } else {
            $error_message = $result;
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
                        <i class="bi bi-person-plus display-4 text-primary"></i>
                        <h2 class="fw-bold mt-3">Rekisteröidy</h2>
                        <p class="text-muted">Luo tili ja aloita digitaalisten taitojen oppiminen</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Koko nimi</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                            </div>
                        </div>

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

                        <div class="mb-3">
                            <label for="password" class="form-label">Salasana</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="form-text">Vähintään 6 merkkiä</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Vahvista salasana</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-person-plus"></i> Rekisteröidy
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="mb-0">Onko sinulla jo tili? 
                            <a href="login.php" class="text-decoration-none">Kirjaudu sisään</a>
                        </p>
                    </div>
                </div>
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
