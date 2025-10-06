<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>DigiTaidot Kuntoon!</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- PayPal SDK -->
    <?php if (isset($page_title) && $page_title === 'Tilaa palvelu'): ?>
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&vault=true&intent=subscription"></script>
    <?php endif; ?>
</head>
<body>
    <!-- Navigaatio -->
    <nav class="navbar navbar-expand-lg navbar-light bg-primary">
        <div class="container">
            <a class="navbar-brand text-white fw-bold" href="index.php">
                <i class="bi bi-laptop"></i> DigiTaidot Kuntoon!
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">Etusivu</a>
                    </li>
                    
                    <?php if ($auth->isLoggedIn()): ?>
                        <?php if ($auth->hasActiveSubscription()): ?>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="dashboard.php">Kurssit</a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link text-white" href="profile.php">Profiili</a>
                        </li>
                        
                        <?php if ($auth->isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle text-white" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="admin/index.php">Hallintapaneeli</a></li>
                                    <li><a class="dropdown-item" href="admin/add_course.php">Lisää kurssi</a></li>
                                    <li><a class="dropdown-item" href="admin/quizzes.php">Kokeet</a></li>
                                    <li><a class="dropdown-item" href="admin/users.php">Käyttäjät</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="navbar-text text-white me-3">
                                Terve, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                                <?php if ($auth->isAdmin()): ?>
                                    <span class="badge bg-warning text-dark">Admin</span>
                                <?php elseif ($auth->hasActiveSubscription()): ?>
                                    <span class="badge bg-success">Aktiivinen</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Ei tilausta</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Kirjaudu ulos
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="login.php">
                                <i class="bi bi-box-arrow-in-right"></i> Kirjaudu sisään
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="register.php">
                                <i class="bi bi-person-plus"></i> Rekisteröidy
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Virheilmoitukset -->
    <?php if (isset($_GET['error'])): ?>
        <?php
        $error_messages = [
            'login_required' => 'Sinun täytyy kirjautua sisään nähdäksesi tämän sivun.',
            'subscription_required' => 'Sinulla ei ole aktiivista tilausta. Tilaa palvelu nähdäksesi kurssit.',
            'access_denied' => 'Sinulla ei ole oikeuksia tähän sivulle.',
            'login_failed' => 'Väärä sähköpostiosoite tai salasana.',
            'registration_failed' => 'Rekisteröinti epäonnistui. Yritä uudelleen.',
            'paypal_error' => 'PayPal-maksussa tapahtui virhe. Yritä uudelleen.'
        ];
        
        $error_message = $error_messages[$_GET['error']] ?? 'Tuntematon virhe tapahtui.';
        ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Onnistumisilmoitukset -->
    <?php if (isset($_GET['success'])): ?>
        <?php
        $success_messages = [
            'registered' => 'Rekisteröinti onnistui! Voit nyt kirjautua sisään.',
            'logged_in' => 'Kirjautuminen onnistui!',
            'payment_success' => 'Maksu onnistui! Tilaus on nyt aktiivinen.',
            'course_added' => 'Kurssi lisätty onnistuneesti!',
            'course_updated' => 'Kurssi päivitetty onnistuneesti!',
            'course_deleted' => 'Kurssi poistettu onnistuneesti!',
            'quiz_completed' => 'Koe suoritettu! Tulokset on tallennettu.'
        ];
        
        $success_message = $success_messages[$_GET['success']] ?? 'Toiminto suoritettu onnistuneesti!';
        ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pääsisältö -->
    <main>
