<?php
// DigiTaidot Kuntoon! - Asennusohjelma
require_once 'config.php';

$page_title = 'Asennus';
$error_message = '';
$success_message = '';
$step = $_GET['step'] ?? 1;

// Tarkista onko järjestelmä jo asennettu
if (file_exists('installed.lock')) {
    die('Järjestelmä on jo asennettu. Poista installed.lock tiedosto asentaaksesi uudelleen.');
}

?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiTaidot Kuntoon! - Asennus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="bi bi-gear"></i> DigiTaidot Kuntoon! - Asennus
                        </h2>
                    </div>
                    <div class="card-body p-5">
                        
                        <?php if ($step == 1): ?>
                            <!-- Vaihe 1: Tietokantayhteys -->
                            <h4>Vaihe 1: Tietokantayhteys</h4>
                            <p>Tarkista tietokantayhteyden asetukset config.php tiedostosta.</p>
                            
                            <div class="alert alert-info">
                                <h5>Tietokanta-asetukset:</h5>
                                <ul>
                                    <li><strong>Host:</strong> <?php echo DB_HOST; ?></li>
                                    <li><strong>Tietokanta:</strong> <?php echo DB_NAME; ?></li>
                                    <li><strong>Käyttäjä:</strong> <?php echo DB_USER; ?></li>
                                </ul>
                            </div>
                            
                            <?php
                            // Testaa tietokantayhteyttä
                            try {
                                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Tietokantayhteys toimii!</div>';
                            } catch(PDOException $e) {
                                echo '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Tietokantayhteys epäonnistui: ' . $e->getMessage() . '</div>';
                                echo '<p>Varmista että:</p><ul><li>Tietokanta on luotu</li><li>Käyttäjätunnus ja salasana ovat oikein</li><li>database.sql on suoritettu</li></ul>';
                                exit;
                            }
                            ?>
                            
                            <div class="text-end">
                                <a href="?step=2" class="btn btn-primary">Seuraava</a>
                            </div>
                            
                        <?php elseif ($step == 2): ?>
                            <!-- Vaihe 2: PayPal-asetukset -->
                            <h4>Vaihe 2: PayPal-asetukset</h4>
                            <p>Tarkista PayPal API -asetukset config.php tiedostosta.</p>
                            
                            <div class="alert alert-warning">
                                <h5>PayPal-asetukset:</h5>
                                <ul>
                                    <li><strong>Client ID:</strong> <?php echo empty(PAYPAL_CLIENT_ID) || PAYPAL_CLIENT_ID === 'YOUR_PAYPAL_CLIENT_ID_HERE' ? '❌ Ei asetettu' : '✅ Asetettu'; ?></li>
                                    <li><strong>Client Secret:</strong> <?php echo empty(PAYPAL_CLIENT_SECRET) || PAYPAL_CLIENT_SECRET === 'YOUR_PAYPAL_CLIENT_SECRET_HERE' ? '❌ Ei asetettu' : '✅ Asetettu'; ?></li>
                                    <li><strong>Plan ID:</strong> <?php echo empty(PAYPAL_PLAN_ID) || PAYPAL_PLAN_ID === 'P-YOUR_PLAN_ID_HERE' ? '❌ Ei asetettu' : '✅ Asetettu'; ?></li>
                                    <li><strong>Tila:</strong> <?php echo PAYPAL_MODE; ?></li>
                                </ul>
                            </div>
                            
                            <div class="text-end">
                                <a href="?step=1" class="btn btn-outline-secondary">Edellinen</a>
                                <a href="?step=3" class="btn btn-primary">Seuraava</a>
                            </div>
                            
                        <?php elseif ($step == 3): ?>
                            <!-- Vaihe 3: Tarkista tiedostot -->
                            <h4>Vaihe 3: Tiedostojen tarkistus</h4>
                            <p>Tarkistetaan että kaikki tarvittavat tiedostot ovat paikallaan.</p>
                            
                            <?php
                            $required_files = [
                                'config.php' => 'Asetustiedosto',
                                'includes/db.php' => 'Tietokantayhteys',
                                'includes/auth.php' => 'Autentikointi',
                                'includes/functions.php' => 'Apufunktiot',
                                'includes/header.php' => 'Sivun yläosa',
                                'includes/footer.php' => 'Sivun alaosa',
                                'index.php' => 'Etusivu',
                                'register.php' => 'Rekisteröinti',
                                'login.php' => 'Kirjautuminen',
                                'dashboard.php' => 'Käyttäjäpaneeli',
                                'admin/index.php' => 'Admin-paneeli'
                            ];
                            
                            $all_files_ok = true;
                            foreach ($required_files as $file => $description) {
                                if (file_exists($file)) {
                                    echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' . $description . ' (' . $file . ')</div>';
                                } else {
                                    echo '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> ' . $description . ' (' . $file . ') - PUUTTUU!</div>';
                                    $all_files_ok = false;
                                }
                            }
                            ?>
                            
                            <?php if ($all_files_ok): ?>
                                <div class="alert alert-success">
                                    <h5><i class="bi bi-check-circle"></i> Kaikki tiedostot löytyvät!</h5>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <h5><i class="bi bi-x-circle"></i> Jotkut tiedostot puuttuvat!</h5>
                                    <p>Varmista että olet ladannut kaikki tiedostot oikeaan kansioon.</p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-end">
                                <a href="?step=2" class="btn btn-outline-secondary">Edellinen</a>
                                <?php if ($all_files_ok): ?>
                                    <a href="?step=4" class="btn btn-primary">Seuraava</a>
                                <?php endif; ?>
                            </div>
                            
                        <?php elseif ($step == 4): ?>
                            <!-- Vaihe 4: Viimeistely -->
                            <h4>Vaihe 4: Asennus valmis!</h4>
                            
                            <div class="alert alert-success">
                                <h5><i class="bi bi-check-circle"></i> DigiTaidot Kuntoon! on asennettu onnistuneesti!</h5>
                            </div>
                            
                            <h5>Seuraavat askeleet:</h5>
                            <ol>
                                <li>Poista tämä install.php tiedosto turvallisuussyistä</li>
                                <li>Mene etusivulle: <a href="index.php">index.php</a></li>
                                <li>Kirjaudu admin-tilillä:
                                    <ul>
                                        <li><strong>Sähköposti:</strong> arttuz311@gmail.com</li>
                                        <li><strong>Salasana:</strong> admin123</li>
                                    </ul>
                                </li>
                                <li>Aloita kurssien lisääminen admin-paneelista</li>
                            </ol>
                            
                            <div class="alert alert-warning">
                                <h5><i class="bi bi-exclamation-triangle"></i> Tärkeää!</h5>
                                <ul>
                                    <li>Muista päivittää PayPal-asetukset tuotantoympäristöön</li>
                                    <li>Vaihda admin-salasana heti ensimmäisellä kerralla</li>
                                    <li>Tee varmuuskopio tietokannasta</li>
                                </ul>
                            </div>
                            
                            <div class="text-center">
                                <a href="index.php" class="btn btn-success btn-lg">
                                    <i class="bi bi-house"></i> Siirry etusivulle
                                </a>
                            </div>
                            
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
