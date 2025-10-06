<?php
// DigiTaidot Kuntoon! - Käyttäjähallinta
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$page_title = 'Käyttäjähallinta';

// Tarkista admin-oikeudet
$functions->requireAdmin();

$error_message = '';
$success_message = '';

// Hae kaikki käyttäjät
$users = $auth->getAllUsers();

// Käsittele käyttäjän poisto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($user_id && $user_id !== $_SESSION['user_id']) { // Ei voi poistaa itseään
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = 'Käyttäjä poistettu onnistuneesti!';
            
            // Päivitä käyttäjälista
            $users = $auth->getAllUsers();
        } catch(PDOException $e) {
            error_log("Käyttäjän poistovirhe: " . $e->getMessage());
            $error_message = 'Käyttäjän poistaminen epäonnistui.';
        }
    } else {
        $error_message = 'Et voi poistaa omaa tiliäsi.';
    }
}

// Käsittele tilauksen aktivointi/deaktivointi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subscription'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    
    if ($user_id) {
        try {
            if ($action === 'activate') {
                $end_date = date('Y-m-d', strtotime('+1 month'));
                $stmt = $pdo->prepare("UPDATE users SET subscription_active = 1, subscription_end = ? WHERE id = ?");
                $stmt->execute([$end_date, $user_id]);
                $success_message = 'Tilaus aktivoitu onnistuneesti!';
            } elseif ($action === 'deactivate') {
                $stmt = $pdo->prepare("UPDATE users SET subscription_active = 0, subscription_end = NULL WHERE id = ?");
                $stmt->execute([$user_id]);
                $success_message = 'Tilaus deaktivoitu onnistuneesti!';
            }
            
            // Päivitä käyttäjälista
            $users = $auth->getAllUsers();
        } catch(PDOException $e) {
            error_log("Tilauksen muokkausvirhe: " . $e->getMessage());
            $error_message = 'Tilauksen muokkaus epäonnistui.';
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
                    <li class="breadcrumb-item active">Käyttäjähallinta</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>
                    <i class="bi bi-people text-primary"></i> Käyttäjähallinta
                </h2>
                
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary" onclick="exportUsers()">
                        <i class="bi bi-download"></i> Vie käyttäjät
                    </button>
                </div>
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

    <!-- Tilastot -->
    <div class="row mb-4">
        <?php
        $total_users = count($users);
        $active_subscribers = count(array_filter($users, function($user) { return $user['subscription_active']; }));
        $admins = count(array_filter($users, function($user) { return $user['role'] === 'ADMIN'; }));
        ?>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-people display-6 text-primary mb-2"></i>
                    <h3 class="fw-bold"><?php echo $total_users; ?></h3>
                    <p class="text-muted mb-0">Käyttäjää yhteensä</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-person-check display-6 text-success mb-2"></i>
                    <h3 class="fw-bold"><?php echo $active_subscribers; ?></h3>
                    <p class="text-muted mb-0">Aktiivista tilaajaa</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-shield-check display-6 text-warning mb-2"></i>
                    <h3 class="fw-bold"><?php echo $admins; ?></h3>
                    <p class="text-muted mb-0">Admin-käyttäjää</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-percent display-6 text-info mb-2"></i>
                    <h3 class="fw-bold">
                        <?php echo $total_users > 0 ? round(($active_subscribers / $total_users) * 100) : 0; ?>%
                    </h3>
                    <p class="text-muted mb-0">Tilausprosentti</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Käyttäjälista -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nimi</th>
                                    <th>Sähköposti</th>
                                    <th>Rooli</th>
                                    <th>Tilaus</th>
                                    <th>Rekisteröitynyt</th>
                                    <th>Toiminnot</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-info">Sinä</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
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
                                            <?php if ($user['subscription_end']): ?>
                                                <br><small class="text-muted">Päättyy: <?php echo $functions->formatDate($user['subscription_end']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Ei tilausta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo $functions->formatDate($user['created_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <!-- Tilauksen hallinta -->
                                                <?php if ($user['subscription_active']): ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <button type="submit" name="toggle_subscription" class="btn btn-outline-warning btn-sm" 
                                                                onclick="return confirm('Deaktivoida tilausta?')">
                                                            <i class="bi bi-pause"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" action="" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" name="toggle_subscription" class="btn btn-outline-success btn-sm" 
                                                                onclick="return confirm('Aktivoida tilausta?')">
                                                            <i class="bi bi-play"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <!-- Käyttäjän poisto -->
                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Haluatko varmasti poistaa käyttäjän <?php echo htmlspecialchars($user['name']); ?>?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">Oma tili</span>
                                            <?php endif; ?>
                                        </div>
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
</div>

<script>
function exportUsers() {
    // Tässä voit toteuttaa käyttäjien vientitoiminnon
    alert('Käyttäjien vientitoiminto lisätään myöhemmin.');
}
</script>

<?php include '../includes/footer.php'; ?>
