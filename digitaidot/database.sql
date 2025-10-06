-- DigiTaidot Kuntoon! - Tietokantataulut
-- Suorita tämä tiedosto MySQL:ssä ennen sivuston käyttöönottoa

CREATE DATABASE IF NOT EXISTS digitaidot;
USE digitaidot;

-- Käyttäjätaulu
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('ADMIN','USER') DEFAULT 'USER',
  subscription_active BOOLEAN DEFAULT FALSE,
  subscription_end DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kurssitaulu
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  category VARCHAR(100),
  difficulty VARCHAR(50),
  age_group VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kurssimoduulit
CREATE TABLE modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  content TEXT,
  order_index INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Kokeet
CREATE TABLE quizzes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT NOT NULL,
  question TEXT NOT NULL,
  correct_answer VARCHAR(255) NOT NULL,
  options JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Kokeen tulokset
CREATE TABLE quiz_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  quiz_id INT NOT NULL,
  score INT NOT NULL,
  taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Oletusadmin-tili (sähköposti: arttuz311@gmail.com, salasana: admin123)
INSERT INTO users (name, email, password, role, subscription_active) VALUES 
('Admin', 'arttuz311@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ADMIN', TRUE);

-- Esimerkkikurssit
INSERT INTO courses (title, description, category, difficulty, age_group) VALUES 
('Perustietokoneen käyttö', 'Opettele tietokoneen perusteet ja käyttöjärjestelmän hallinta', 'Perusteet', 'Aloittelija', 'Kaikki ikäryhmät'),
('Internet ja verkkoselain', 'Tutustu internetiin ja opettele verkkoselaimen käyttöä', 'Perusteet', 'Aloittelija', 'Kaikki ikäryhmät'),
('Sähköpostin käyttö', 'Opettele sähköpostin lähettämistä ja vastaanottamista', 'Kommunikaatio', 'Aloittelija', 'Kaikki ikäryhmät');

-- Esimerkkimoduulit
INSERT INTO modules (course_id, title, content, order_index) VALUES 
(1, 'Tietokoneen käynnistäminen ja sammuttaminen', 'Tässä moduulissa opettelet tietokoneen käynnistämisen ja sammuttamisen oikeat tavat...', 1),
(1, 'Hiiren ja näppäimistön käyttö', 'Hiiri ja näppäimistö ovat tietokoneen tärkeimmät syöttövälineet...', 2),
(2, 'Internetin perusteet', 'Internet on maailmanlaajuinen tietoverkko...', 1),
(2, 'Verkkoselaimen käyttö', 'Verkkoselain on ohjelma, jolla voit selata internetsivuja...', 2);

-- Esimerkkikokeet
INSERT INTO quizzes (course_id, question, correct_answer, options) VALUES 
(1, 'Mikä on tietokoneen käynnistämisen oikea tapa?', 'Käynnistä-painike', '["Käynnistä-painike", "Kytke virta pois", "Paina Ctrl+Alt+Delete", "Avaa kansio"]'),
(1, 'Mitä tarkoittaa "kaksoisklikkaus"?', 'Klikkaa hiirtä nopeasti kaksi kertaa', '["Klikkaa hiirtä nopeasti kaksi kertaa", "Pidä hiiri pohjassa", "Klikkaa hiirtä kolme kertaa", "Käännä hiiri ympäri"]'),
(2, 'Mikä on internetin lyhenne?', 'World Wide Web', '["World Wide Web", "Wide Web World", "Web World Wide", "Wide World Web"]');
