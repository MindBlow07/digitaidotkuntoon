# DigiTaidot Kuntoon! - PHP Oppimisalusta

Täysi PHP-pohjainen oppimisalusta digitaalisten taitojen oppimiseen.

## 🎯 Ominaisuudet

- **Käyttäjähallinta**: Rekisteröinti, kirjautuminen, profiilit
- **Maksujärjestelmä**: PayPal Subscriptions API integraatio (5 €/kk)
- **Kurssit**: Moduuleihin jaettua sisältöä
- **Kokeet**: Interaktiiviset testit kurssien jälkeen
- **Admin-paneeli**: Kurssien, kokeiden ja käyttäjien hallinta
- **Responsiivinen design**: Bootstrap 5 + mukautetut tyylit

## 🚀 Asennusohjeet

### 1. Tiedostojen lataus
Lataa kaikki tiedostot web-palvelimellesi `/digitaidot/` kansioon.

### 2. Tietokanta
```sql
-- Luo tietokanta ja suorita database.sql
mysql -u root -p < database.sql
```

### 3. Asetukset
Muokkaa `config.php` tiedostoa:
```php
// Tietokanta-asetukset
define('DB_HOST', 'localhost');
define('DB_NAME', 'digitaidot');
define('DB_USER', 'root');
define('DB_PASS', '');

// PayPal API -asetukset
define('PAYPAL_CLIENT_ID', 'YOUR_PAYPAL_CLIENT_ID_HERE');
define('PAYPAL_CLIENT_SECRET', 'YOUR_PAYPAL_CLIENT_SECRET_HERE');
define('PAYPAL_PLAN_ID', 'P-YOUR_PLAN_ID_HERE');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' tai 'live'
```

### 4. PayPal Setup
1. Luo PayPal Developer Account
2. Luo uusi App ja hae Client ID ja Secret
3. Luo Subscription Plan (5 €/kk)
4. Päivitä config.php tiedostoon

### 5. Oikeudet
```bash
chmod 755 css/
chmod 755 js/
chmod 755 includes/
chmod 644 *.php
```

## 🔐 Admin-tili

Oletusadmin-tili on luotu automaattisesti:
- **Sähköposti**: arttuz311@gmail.com
- **Salasana**: admin123
- **Rooli**: ADMIN (ei tilausta vaadita)

## 📁 Kansiorakenne

```
/digitaidot/
├── index.php              # Etusivu / kirjautuminen
├── register.php           # Käyttäjän rekisteröinti
├── login.php              # Kirjautuminen
├── logout.php             # Uloskirjautuminen
├── dashboard.php          # Kurssilista maksaneille käyttäjille
├── course.php             # Yksittäinen kurssi
├── quiz.php               # Kurssin lopputesti
├── profile.php            # Käyttäjän profiili
├── success.php            # PayPal onnistui
├── cancel.php             # PayPal peruutettu
├── config.php             # Ympäristöasetukset
├── database.sql           # Tietokantataulut
├── admin/                 # Admin-paneeli
│   ├── index.php          # Adminin etusivu
│   ├── add_course.php     # Kurssin lisäys
│   ├── edit_course.php    # Kurssin muokkaus
│   ├── quizzes.php        # Kokeiden hallinta
│   └── users.php          # Käyttäjähallinta
├── includes/              # PHP-luokat ja funktiot
│   ├── db.php             # MySQL-yhteys (PDO)
│   ├── auth.php           # Autentikointi
│   ├── functions.php      # Apufunktiot
│   ├── header.php         # Sivun yläosa
│   └── footer.php         # Sivun alaosa
├── css/
│   └── style.css          # Mukautetut tyylit
└── js/
    └── main.js            # JavaScript-toiminnot
```

## 🎨 Tekniikat

- **Backend**: PHP 8.x, PDO, bcrypt
- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Tietokanta**: MySQL
- **Maksut**: PayPal Subscriptions API
- **Turvallisuus**: SQL injection suojaus, XSS-suojaus, bcrypt salasanoille

## 📊 Tietokantataulut

### users
- Käyttäjätiedot, roolit, tilaukset

### courses  
- Kurssien perustiedot

### modules
- Kurssimoduulit ja sisältö

### quizzes
- Kokeet ja vastausvaihtoehdot

### quiz_results
- Kokeiden tulokset

## 🔧 Käyttöohjeet

### Käyttäjälle
1. Rekisteröidy sivustolle
2. Kirjaudu sisään
3. Tilaa palvelu PayPalilla (5 €/kk)
4. Selaa kursseja dashboardissa
5. Suorita kursseja ja kokeita

### Adminille
1. Kirjaudu admin-tilillä
2. Mene admin-paneeliin
3. Lisää/muokkaa kursseja
4. Hallinnoi kokeita
5. Seuraa käyttäjien tilauksia

## 🛡️ Turvallisuus

- Kaikki käyttäjän syötteet validoidaan ja puhdistetaan
- Salasanat hashataan bcrypt:llä
- SQL injection suojaus PDO:lla
- XSS-suojaus htmlspecialchars:lla
- Session-hallinta turvallisesti

## 🚀 Kehitysympäristö

### Paikallinen kehitys
```bash
# Käynnistä paikallinen palvelin
php -S localhost:8000

# Avaa selaimessa
http://localhost:8000
```

### Docker (valinnainen)
```dockerfile
FROM php:8.1-apache
COPY . /var/www/html/
RUN docker-php-ext-install pdo pdo_mysql
```

## 📝 Lisenssi

Tämä projekti on luotu opetustarkoituksiin.

## 🆘 Tuki

Ongelmatilanteissa ota yhteyttä: arttuz311@gmail.com

## 🔄 Päivitykset

### Versio 1.0.0
- Perustoiminnot
- PayPal integraatio
- Admin-paneeli
- Responsiivinen design

### Tulevaisuudessa
- Lisää kurssisisältöä
- Edistymisseuranta
- Sertifikaatit
- Mobiilisovellus
