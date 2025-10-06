<?php
// Ympäristöasetukset DigiTaidot Kuntoon! -sivustolle

// Tietokanta-asetukset
define('DB_HOST', 'localhost');
define('DB_NAME', 'digitaidot');
define('DB_USER', 'root');
define('DB_PASS', '');

// PayPal API -asetukset (Käytä sandbox-testejä kehityksessä!)
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID_HERE');
define('PAYPAL_CLIENT_SECRET', 'YOUR_PAYPAL_CLIENT_SECRET_HERE');
define('PAYPAL_PLAN_ID', 'P-YOUR_PLAN_ID_HERE');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' tai 'live'

// Sivuston asetukset
define('SITE_URL', 'http://localhost/digitaidot');
define('SITE_NAME', 'DigiTaidot Kuntoon!');
define('ADMIN_EMAIL', 'arttuz311@gmail.com');

// Salausasetukset
define('ENCRYPTION_KEY', 'your-secret-key-here-change-this');

// Session-asetukset
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Vaihda 1:ksi HTTPS:n kanssa
session_start();
?>
