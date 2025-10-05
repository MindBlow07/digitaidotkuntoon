#!/bin/bash

# DigiTaidot kuntoon! - Automaatioasetusskripti
# Tämä skripti asentaa kaikki tarvittavat komponentit oppimisalustalle

set -e  # Lopeta virheiden sattuessa

echo "🎓 DigiTaidot kuntoon! - Automaatioasetus"
echo "=========================================="

# Värit terminaaliin
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funktio viestien tulostamiseen
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Tarkista että ollaan Linux-järjestelmässä
if [[ "$OSTYPE" != "linux-gnu"* ]]; then
    print_error "Tämä skripti on suunniteltu Linux-järjestelmille!"
    exit 1
fi

print_status "Aloitetaan DigiTaidot kuntoon! -oppimisalustan asennus..."

# Päivitä pakettilista
print_status "Päivitetään pakettilista..."
sudo apt update

# Asenna perustarvikkeet
print_status "Asennetaan perustarvikkeet..."
sudo apt install -y curl wget git nodejs npm python3 python3-pip postgresql postgresql-contrib nginx

# Tarkista Node.js versio
NODE_VERSION=$(node --version)
print_success "Node.js asennettu: $NODE_VERSION"

# Tarkista Python versio
PYTHON_VERSION=$(python3 --version)
print_success "Python asennettu: $PYTHON_VERSION"

# Asenna PM2 globaalisti (Node.js prosessien hallintaan)
print_status "Asennetaan PM2..."
sudo npm install -g pm2

# Asenna PostgreSQL ja luo tietokanta
print_status "Konfiguroidaan PostgreSQL..."
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Luo tietokanta ja käyttäjä
print_status "Luodaan tietokanta ja käyttäjä..."
sudo -u postgres psql -c "CREATE DATABASE digitaidot_kuntoon;"
sudo -u postgres psql -c "CREATE USER digitaidot_user WITH ENCRYPTED PASSWORD 'digitaidot_secure_2025';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE digitaidot_kuntoon TO digitaidot_user;"
sudo -u postgres psql -c "ALTER USER digitaidot_user CREATEDB;"

print_success "PostgreSQL konfiguroitu!"

# Luo .env tiedosto backend-kansioon
print_status "Luodaan ympäristömuuttujat..."
cat > backend/.env << EOF
# DigiTaidot kuntoon! - Ympäristömuuttujat
NODE_ENV=production
PORT=3000

# Tietokanta
DB_HOST=localhost
DB_PORT=5432
DB_NAME=digitaidot_kuntoon
DB_USER=digitaidot_user
DB_PASSWORD=digitaidot_secure_2025

# JWT
JWT_SECRET=digitaidot_jwt_secret_key_2025_very_secure
JWT_EXPIRES_IN=7d

# Stripe (maksujen käsittely)
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key_here
STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_publishable_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here

# Tilaushinta
SUBSCRIPTION_PRICE=599  # 5.99€ sentteinä

# Email (valinnainen)
EMAIL_SERVICE=gmail
EMAIL_USER=your_email@gmail.com
EMAIL_PASS=your_app_password

# Frontend URL
FRONTEND_URL=http://localhost:3001
EOF

print_success "Ympäristömuuttujat luotu!"

# Asenna backend riippuvuudet
print_status "Asennetaan backend riippuvuudet..."
cd backend
npm init -y
npm install express cors helmet morgan bcryptjs jsonwebtoken pg stripe nodemailer dotenv
npm install -D nodemon

# Luo package.json skriptit
cat > package.json << EOF
{
  "name": "digitaidot-backend",
  "version": "1.0.0",
  "description": "DigiTaidot kuntoon! - Backend API",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js",
    "setup-db": "node scripts/setup-database.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "cors": "^2.8.5",
    "helmet": "^7.1.0",
    "morgan": "^1.10.0",
    "bcryptjs": "^2.4.3",
    "jsonwebtoken": "^9.0.2",
    "pg": "^8.11.3",
    "stripe": "^14.12.0",
    "nodemailer": "^6.9.8",
    "dotenv": "^16.3.1"
  },
  "devDependencies": {
    "nodemon": "^3.0.2"
  }
}
EOF

# Asenna frontend riippuvuudet
print_status "Asennetaan frontend riippuvuudet..."
cd ../frontend
npm init -y
npm install react react-dom react-router-dom axios
npm install -D @vitejs/plugin-react vite

# Luo Vite konfiguraatio
cat > vite.config.js << EOF
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3001,
    proxy: {
      '/api': 'http://localhost:3000'
    }
  }
})
EOF

# Luo package.json skriptit frontendille
cat > package.json << EOF
{
  "name": "digitaidot-frontend",
  "version": "1.0.0",
  "description": "DigiTaidot kuntoon! - Frontend",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.20.1",
    "axios": "^1.6.2"
  },
  "devDependencies": {
    "@vitejs/plugin-react": "^4.2.0",
    "vite": "^5.0.0"
  }
}
EOF

cd ..

# Luo tietokannan setup-skripti
print_status "Luodaan tietokannan setup-skripti..."
mkdir -p backend/scripts
cat > backend/scripts/setup-database.js << 'EOF'
const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
});

async function setupDatabase() {
  try {
    console.log('🗄️  Luodaan tietokantataulut...');

    // Käyttäjät-taulu
    await pool.query(`
      CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        subscription_active BOOLEAN DEFAULT FALSE,
        subscription_end_date TIMESTAMP,
        stripe_customer_id VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Kurssit-taulu
    await pool.query(`
      CREATE TABLE IF NOT EXISTS courses (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        category VARCHAR(100) NOT NULL,
        difficulty VARCHAR(50) DEFAULT 'alkeet',
        duration_minutes INTEGER DEFAULT 0,
        is_premium BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Oppitunnit-taulu
    await pool.query(`
      CREATE TABLE IF NOT EXISTS lessons (
        id SERIAL PRIMARY KEY,
        course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        video_url VARCHAR(500),
        order_index INTEGER NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Quiz-kysymykset
    await pool.query(`
      CREATE TABLE IF NOT EXISTS quiz_questions (
        id SERIAL PRIMARY KEY,
        lesson_id INTEGER REFERENCES lessons(id) ON DELETE CASCADE,
        question TEXT NOT NULL,
        options JSONB NOT NULL,
        correct_answer INTEGER NOT NULL,
        explanation TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Käyttäjien edistyminen
    await pool.query(`
      CREATE TABLE IF NOT EXISTS user_progress (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        course_id INTEGER REFERENCES courses(id) ON DELETE CASCADE,
        lesson_id INTEGER REFERENCES lessons(id) ON DELETE CASCADE,
        completed BOOLEAN DEFAULT FALSE,
        completed_at TIMESTAMP,
        quiz_score INTEGER DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Tilaushistoria
    await pool.query(`
      CREATE TABLE IF NOT EXISTS subscriptions (
        id SERIAL PRIMARY KEY,
        user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
        stripe_subscription_id VARCHAR(255) UNIQUE,
        status VARCHAR(50) NOT NULL,
        current_period_start TIMESTAMP,
        current_period_end TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      )
    `);

    console.log('✅ Tietokantataulut luotu onnistuneesti!');
    
    // Lisää testikurssit
    console.log('📚 Lisätään testikurssit...');
    
    const courses = [
      {
        title: 'Tietoturva kodissa - Perusteet',
        description: 'Opettele suojautumaan tietoturvariskeiltä kotona. Sisältää perheiden tietoturvan, lasten turvallisuuden ja IoT-laitteiden suojauksen.',
        category: 'Tietoturva',
        difficulty: 'alkeet',
        duration_minutes: 120,
        is_premium: false
      },
      {
        title: 'Tietoturva työpaikalla',
        description: 'Yrityksen tietoturva, sähköpostin suojaus, VPN ja etätyön turvallisuus.',
        category: 'Tietoturva',
        difficulty: 'keskitaso',
        duration_minutes: 180,
        is_premium: true
      },
      {
        title: 'Python ohjelmoinnin alkeet',
        description: 'Opettele Python-ohjelmointikielen perusteet käytännön esimerkkien avulla.',
        category: 'Ohjelmointi',
        difficulty: 'alkeet',
        duration_minutes: 240,
        is_premium: true
      },
      {
        title: 'Digi-aiheet nykypäivään',
        description: 'Sosiaalinen media, pilvipalvelut, mobiililaitteet ja digitaaliset taidot.',
        category: 'Digi-aiheet',
        difficulty: 'alkeet',
        duration_minutes: 150,
        is_premium: false
      }
    ];

    for (const course of courses) {
      await pool.query(
        'INSERT INTO courses (title, description, category, difficulty, duration_minutes, is_premium) VALUES ($1, $2, $3, $4, $5, $6) ON CONFLICT DO NOTHING',
        [course.title, course.description, course.category, course.difficulty, course.duration_minutes, course.is_premium]
      );
    }

    console.log('✅ Testikurssit lisätty!');
    console.log('🎉 Tietokannan setup valmis!');

  } catch (error) {
    console.error('❌ Virhe tietokannan luonnissa:', error);
    process.exit(1);
  } finally {
    await pool.end();
  }
}

setupDatabase();
EOF

# Luo PM2 konfiguraatio
print_status "Luodaan PM2 konfiguraatio..."
cat > ecosystem.config.js << EOF
module.exports = {
  apps: [
    {
      name: 'digitaidot-backend',
      script: './backend/server.js',
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      },
      error_file: './logs/backend-error.log',
      out_file: './logs/backend-out.log',
      log_file: './logs/backend-combined.log',
      time: true
    },
    {
      name: 'digitaidot-frontend',
      script: 'serve',
      args: '-s frontend/dist -l 3001',
      instances: 1,
      exec_mode: 'fork',
      env: {
        NODE_ENV: 'production'
      },
      error_file: './logs/frontend-error.log',
      out_file: './logs/frontend-out.log',
      log_file: './logs/frontend-combined.log',
      time: true
    }
  ]
};
EOF

# Luo logs-kansio
mkdir -p logs

# Asenna serve (staattisten tiedostojen palvelimelle)
sudo npm install -g serve

# Luo systemd palvelu (valinnainen)
print_status "Luodaan systemd palvelu..."
sudo tee /etc/systemd/system/digitaidot.service > /dev/null << EOF
[Unit]
Description=DigiTaidot kuntoon! - Oppimisalusta
After=network.target postgresql.service

[Service]
Type=forking
User=$USER
WorkingDirectory=$(pwd)
ExecStart=/usr/bin/pm2 start ecosystem.config.js --env production
ExecReload=/usr/bin/pm2 reload all
ExecStop=/usr/bin/pm2 stop all
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Konfiguroi Nginx
print_status "Konfiguroidaan Nginx..."
sudo tee /etc/nginx/sites-available/digitaidot << EOF
server {
    listen 80;
    server_name localhost;

    # Frontend
    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }

    # Backend API
    location /api {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }
}
EOF

# Ota Nginx konfiguraatio käyttöön
sudo ln -sf /etc/nginx/sites-available/digitaidot /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl enable nginx

# Käynnistä tietokanta
print_status "Käynnistetään tietokanta..."
cd backend
npm run setup-db
cd ..

print_success "🎉 DigiTaidot kuntoon! -oppimisalusta asennettu onnistuneesti!"
print_status "Seuraavat vaiheet:"
echo ""
echo "1. 🔧 Konfiguroi Stripe-avaimet backend/.env tiedostossa"
echo "2. 🌐 Konfiguroi Nginx (ohjeet näkyvät yllä)"
echo "3. 🚀 Käynnistä sovellus: pm2 start ecosystem.config.js"
echo "4. 🌐 Avaa selaimessa: http://localhost"
echo "5. 📊 Seuraa lokitiedostoja: pm2 logs"
echo ""
echo "📁 Projektin rakenne:"
echo "├── frontend/     - React-sovellus"
echo "├── backend/      - Node.js API"
echo "├── database/     - PostgreSQL tietokanta"
echo "├── scripts/      - Apuskriptit"
echo "└── logs/         - Sovelluksen lokit"
echo ""
echo "💰 Tilaushinta: 5,99€/kk"
echo "🔐 Tietokanta: digitaidot_kuntoon"
echo "👤 Käyttäjä: digitaidot_user"
echo ""
print_warning "Muista vaihtaa salasanat tuotantokäytössä!"
