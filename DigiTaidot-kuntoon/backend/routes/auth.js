const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { Pool } = require('pg');

const router = express.Router();
const pool = new Pool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
});

// Rekisteröinti
router.post('/register', async (req, res) => {
  try {
    const { email, password, firstName, lastName } = req.body;

    // Validoi syötteet
    if (!email || !password || !firstName || !lastName) {
      return res.status(400).json({ error: 'Kaikki kentät ovat pakollisia' });
    }

    if (password.length < 6) {
      return res.status(400).json({ error: 'Salasanan tulee olla vähintään 6 merkkiä' });
    }

    // Tarkista onko käyttäjä jo olemassa
    const existingUser = await pool.query(
      'SELECT id FROM users WHERE email = $1',
      [email]
    );

    if (existingUser.rows.length > 0) {
      return res.status(400).json({ error: 'Sähköposti on jo käytössä' });
    }

    // Hashaa salasana
    const saltRounds = 12;
    const passwordHash = await bcrypt.hash(password, saltRounds);

    // Luo käyttäjä (aina student-rooliin, paitsi jos on opettajasähköposti)
    const role = email === 'arttuz311@gmail.com' ? 'teacher' : 'student';
    const result = await pool.query(
      'INSERT INTO users (email, password_hash, first_name, last_name, role) VALUES ($1, $2, $3, $4, $5) RETURNING id, email, first_name, last_name, subscription_active, role',
      [email, passwordHash, firstName, lastName, role]
    );

    const user = result.rows[0];

    // Luo JWT token
    const token = jwt.sign(
      { userId: user.id, email: user.email },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
    );

    res.status(201).json({
      message: 'Käyttäjä luotu onnistuneesti!',
      user: {
        id: user.id,
        email: user.email,
        firstName: user.first_name,
        lastName: user.last_name,
        role: user.role,
        subscriptionActive: user.subscription_active
      },
      token
    });

  } catch (error) {
    console.error('Rekisteröintivirhe:', error);
    res.status(500).json({ error: 'Rekisteröinti epäonnistui' });
  }
});

// Kirjautuminen
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;

    // Validoi syötteet
    if (!email || !password) {
      return res.status(400).json({ error: 'Sähköposti ja salasana vaaditaan' });
    }

    // Hae käyttäjä
    const result = await pool.query(
      'SELECT id, email, password_hash, first_name, last_name, role, subscription_active, subscription_end_date FROM users WHERE email = $1',
      [email]
    );

    if (result.rows.length === 0) {
      return res.status(401).json({ error: 'Väärä sähköposti tai salasana' });
    }

    const user = result.rows[0];

    // Tarkista salasana
    const passwordMatch = await bcrypt.compare(password, user.password_hash);
    if (!passwordMatch) {
      return res.status(401).json({ error: 'Väärä sähköposti tai salasana' });
    }

    // Tarkista tilauksen voimassaolo
    const subscriptionActive = user.subscription_active && 
      (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

    // Päivitä tilauksen tila tarvittaessa
    if (user.subscription_active && !subscriptionActive) {
      await pool.query(
        'UPDATE users SET subscription_active = false WHERE id = $1',
        [user.id]
      );
    }

    // Luo JWT token
    const token = jwt.sign(
      { userId: user.id, email: user.email },
      process.env.JWT_SECRET,
      { expiresIn: process.env.JWT_EXPIRES_IN || '7d' }
    );

    res.json({
      message: 'Kirjautuminen onnistui!',
      user: {
        id: user.id,
        email: user.email,
        firstName: user.first_name,
        lastName: user.last_name,
        role: user.role,
        subscriptionActive: subscriptionActive
      },
      token
    });

  } catch (error) {
    console.error('Kirjautumisvirhe:', error);
    res.status(500).json({ error: 'Kirjautuminen epäonnistui' });
  }
});

// Token validointi middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Pääsy kielletty - token puuttuu' });
  }

  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Pääsy kielletty - virheellinen token' });
    }
    req.user = user;
    next();
  });
};

// Käyttäjätietojen haku
router.get('/me', authenticateToken, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT id, email, first_name, last_name, role, subscription_active, subscription_end_date, created_at FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    const user = result.rows[0];
    const subscriptionActive = user.subscription_active && 
      (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

    res.json({
      user: {
        id: user.id,
        email: user.email,
        firstName: user.first_name,
        lastName: user.last_name,
        role: user.role,
        subscriptionActive,
        subscriptionEndDate: user.subscription_end_date,
        createdAt: user.created_at
      }
    });

  } catch (error) {
    console.error('Käyttäjätietojen hakuvirhe:', error);
    res.status(500).json({ error: 'Käyttäjätietojen haku epäonnistui' });
  }
});

module.exports = router;
