<?php
// DigiTaidot Kuntoon! - Etusivu
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Etusivu';

// Jos käyttäjä on jo kirjautunut ja hänellä on aktiivinen tilaus, ohjaa dashboardiin
if ($auth->isLoggedIn() && $auth->hasActiveSubscription()) {
    header('Location: dashboard.php');
    exit();
}

include 'includes/header.php';
?>

<!-- Hero-osio -->
<section class="hero-section bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center min-vh-75">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="bi bi-laptop text-warning"></i><br>
                    DigiTaidot Kuntoon!
                </h1>
                <p class="lead mb-4">
                    Paranna digitaalisia taitojasi helposti ja kustannustehokkaasti. 
                    Opettele tietokoneen käyttöä, internetselailua ja sähköpostin hallintaa 
                    omassa tahdissasi.
                </p>
                
                <?php if (!$auth->isLoggedIn()): ?>
                    <div class="d-flex gap-3 mb-4">
                        <a href="register.php" class="btn btn-warning btn-lg">
                            <i class="bi bi-person-plus"></i> Aloita täältä
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Kirjaudu sisään
                        </a>
                    </div>
                <?php elseif (!$auth->hasActiveSubscription()): ?>
                    <div class="d-flex gap-3 mb-4">
                        <a href="#subscription" class="btn btn-warning btn-lg">
                            <i class="bi bi-credit-card"></i> Tilaa palvelu
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-house"></i> Siirry dashboardiin
                        </a>
                    </div>
                <?php endif; ?>
                
                <div class="d-flex align-items-center text-light">
                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                    <span>Vain 5 €/kk - Peruuta milloin tahansa</span>
                </div>
            </div>
            
            <div class="col-lg-6 text-center">
                <div class="hero-image">
                    <i class="bi bi-laptop display-1 text-warning opacity-75"></i>
                    <div class="mt-4">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="fw-bold">3+</h3>
                                    <small>Kurssia</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="fw-bold">10+</h3>
                                    <small>Moduulia</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="fw-bold">24/7</h3>
                                    <small>Pääsy</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kurssien esittely -->
<section class="py-5" id="courses">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Saatavilla olevat kurssit</h2>
                <p class="lead text-muted">Opettele digitaalisia taitoja vaihe vaiheelta</p>
            </div>
        </div>
        
        <div class="row">
            <?php
            $courses = $functions->getAllCourses();
            foreach ($courses as $course):
            ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm course-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-book text-primary"></i>
                            <?php echo htmlspecialchars($course['title']); ?>
                        </h5>
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?>
                        </p>
                        
                        <div class="course-meta mb-3">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($course['category']); ?></span>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($course['difficulty']); ?></span>
                            <span class="badge bg-info"><?php echo htmlspecialchars($course['age_group']); ?></span>
                        </div>
                        
                        <?php if ($auth->hasActiveSubscription()): ?>
                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-play-circle"></i> Aloita kurssi
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-lock"></i> Tilausta vaaditaan
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Tilaus-osio -->
<?php if ($auth->isLoggedIn() && !$auth->hasActiveSubscription()): ?>
<section class="py-5 bg-light" id="subscription">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="display-5 fw-bold mb-4">Tilaa palvelu nyt</h2>
                <p class="lead mb-4">
                    Saat pääsyn kaikkiin kursseihin ja kokeisiin vain 5 €/kk. 
                    Peruuta milloin tahansa ilman lisäkustannuksia.
                </p>
                
                <div class="pricing-card bg-white p-4 rounded shadow mb-4">
                    <h3 class="fw-bold text-primary mb-3">
                        <i class="bi bi-star-fill text-warning"></i>
                        Premium-tilaus
                    </h3>
                    <div class="price-display mb-3">
                        <span class="display-4 fw-bold text-primary">5 €</span>
                        <span class="text-muted">/kk</span>
                    </div>
                    
                    <ul class="list-unstyled text-start mb-4">
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Pääsy kaikkiin kursseihin
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Interaktiiviset kokeet ja testit
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            24/7 pääsy sisältöön
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Todistukset kurssien suorittamisesta
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Peruuta milloin tahansa
                        </li>
                    </ul>
                    
                    <!-- PayPal-tilauspainike -->
                    <div id="paypal-button-container" class="mt-4"></div>
                </div>
                
                <p class="small text-muted">
                    <i class="bi bi-shield-check"></i>
                    Turvallinen maksu PayPalin kautta. Peruuta milloin tahansa.
                </p>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Etuja -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="display-5 fw-bold">Miksi valita DigiTaidot Kuntoon!</h2>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-clock-history display-4 text-primary"></i>
                    </div>
                    <h4>Oma tahti</h4>
                    <p class="text-muted">Opiskele milloin haluat ja omassa tahdissasi. Sisältö on saatavilla 24/7.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-award display-4 text-primary"></i>
                    </div>
                    <h4>Käytännönläheinen</h4>
                    <p class="text-muted">Opettele oikeita taitoja, joita tarvitset arjessa ja työssä.</p>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="text-center">
                    <div class="feature-icon mb-3">
                        <i class="bi bi-people display-4 text-primary"></i>
                    </div>
                    <h4>Kaikenikäisille</h4>
                    <p class="text-muted">Kurssit sopivat kaikille, oli kyse aloittelijasta vai kokeneemmasta käyttäjästä.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
$page_title = 'Tilaa palvelu'; // PayPal-koodin käyttöön
include 'includes/footer.php'; 
?>
