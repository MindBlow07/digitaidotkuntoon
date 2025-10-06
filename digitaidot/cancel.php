<?php
// DigiTaidot Kuntoon! - PayPal-maksun peruuttaminen
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$page_title = 'Maksu peruutettu';

// Tarkista kirjautuminen
$functions->requireLogin();

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Peruutusviesti -->
            <div class="card border-warning">
                <div class="card-body text-center p-5">
                    <div class="warning-icon mb-4">
                        <i class="bi bi-x-circle-fill display-1 text-warning"></i>
                    </div>
                    
                    <h2 class="fw-bold text-warning mb-3">Maksu peruutettu</h2>
                    
                    <p class="lead mb-4">
                        Maksuprosessi peruutettiin. Tilaus ei ole aktivoitu, 
                        mutta voit yrittää maksua uudelleen milloin tahansa.
                    </p>
                    
                    <div class="alert alert-info mb-4">
                        <h5><i class="bi bi-info-circle"></i> Mitä tapahtuu seuraavaksi?</h5>
                        <ul class="list-unstyled mb-0 text-start">
                            <li><i class="bi bi-arrow-right text-primary"></i> Tilaus ei ole aktivoitu</li>
                            <li><i class="bi bi-arrow-right text-primary"></i> Pääsy kursseihin vaatii aktiivisen tilauksen</li>
                            <li><i class="bi bi-arrow-right text-primary"></i> Voit yrittää maksua uudelleen milloin tahansa</li>
                            <li><i class="bi bi-arrow-right text-primary"></i> Tili ja rekisteröintitiedot säilyvät</li>
                        </ul>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="index.php#subscription" class="btn btn-primary btn-lg">
                            <i class="bi bi-credit-card"></i> Yritä maksua uudelleen
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-house"></i> Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maksutietoja -->
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <h5><i class="bi bi-credit-card"></i> Tietoa maksusta</h5>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Maksutiedot:</h6>
                            <ul>
                                <li><strong>Hinta:</strong> 5 €/kk</li>
                                <li><strong>Maksutapa:</strong> PayPal</li>
                                <li><strong>Peruutus:</strong> Milloin tahansa</li>
                                <li><strong>Uusinta:</strong> Automaattinen</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Saatavissa:</h6>
                            <ul>
                                <li><i class="bi bi-check text-success"></i> Kaikki kurssit</li>
                                <li><i class="bi bi-check text-success"></i> Interaktiiviset kokeet</li>
                                <li><i class="bi bi-check text-success"></i> 24/7 pääsy</li>
                                <li><i class="bi bi-check text-success"></i> Todistukset</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Tarvitsetko apua maksussa?</strong> 
                        Ota yhteyttä tukeen: <strong>arttuz311@gmail.com</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yleisiä kysymyksiä -->
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-question-circle"></i> Usein kysytyt kysymykset</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq1">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                    Voinko peruuttaa tilauksen milloin tahansa?
                                </button>
                            </h2>
                            <div id="collapse1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Kyllä! Voit peruuttaa tilauksesi milloin tahansa profiilisivulta. 
                                    Pääsy säilyy tilauskauden loppuun asti.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq2">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                    Miten maksu tapahtuu?
                                </button>
                            </h2>
                            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Maksu tapahtuu turvallisesti PayPalin kautta. 
                                    Tilaus uusiutuu automaattisesti kuukausittain.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faq3">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                    Saanko palautuksen jos en ole tyytyväinen?
                                </button>
                            </h2>
                            <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Tarjoamme 30 päivän tyytyväisyystakuun. 
                                    Jos et ole tyytyväinen palveluun, ota yhteyttä tukeen.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
