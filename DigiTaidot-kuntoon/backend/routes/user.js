const express = require('express');
const bcrypt = require('bcryptjs');
const { Pool } = require('pg');

const router = express.Router();
const pool = new Pool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
});

// Token validointi middleware
const authenticateToken = (req, res, next) => {
  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1];

  if (!token) {
    return res.status(401).json({ error: 'Pääsy kielletty - token puuttuu' });
  }

  const jwt = require('jsonwebtoken');
  jwt.verify(token, process.env.JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Pääsy kielletty - virheellinen token' });
    }
    req.user = user;
    next();
  });
};

// Hae käyttäjätiedot
router.get('/profile', authenticateToken, async (req, res) => {
  try {
    const result = await pool.query(
      'SELECT id, email, first_name, last_name, subscription_active, subscription_end_date, created_at FROM users WHERE id = $1',
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
        subscriptionActive,
        subscriptionEndDate: user.subscription_end_date,
        createdAt: user.created_at,
        memberSince: user.created_at
      }
    });

  } catch (error) {
    console.error('Profiilin hakuvirhe:', error);
    res.status(500).json({ error: 'Profiilin haku epäonnistui' });
  }
});

// Päivitä käyttäjätiedot
router.put('/profile', authenticateToken, async (req, res) => {
  try {
    const { firstName, lastName } = req.body;

    if (!firstName || !lastName) {
      return res.status(400).json({ error: 'Etunimi ja sukunimi ovat pakollisia' });
    }

    const result = await pool.query(
      'UPDATE users SET first_name = $1, last_name = $2, updated_at = CURRENT_TIMESTAMP WHERE id = $3 RETURNING first_name, last_name',
      [firstName, lastName, req.user.userId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    res.json({
      message: 'Profiili päivitetty onnistuneesti!',
      user: {
        firstName: result.rows[0].first_name,
        lastName: result.rows[0].last_name
      }
    });

  } catch (error) {
    console.error('Profiilin päivitysvirhe:', error);
    res.status(500).json({ error: 'Profiilin päivitys epäonnistui' });
  }
});

// Vaihda salasana
router.put('/password', authenticateToken, async (req, res) => {
  try {
    const { currentPassword, newPassword } = req.body;

    if (!currentPassword || !newPassword) {
      return res.status(400).json({ error: 'Nykyinen ja uusi salasana vaaditaan' });
    }

    if (newPassword.length < 6) {
      return res.status(400).json({ error: 'Uusi salasana tulee olla vähintään 6 merkkiä' });
    }

    // Hae nykyinen salasana
    const result = await pool.query(
      'SELECT password_hash FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    // Tarkista nykyinen salasana
    const passwordMatch = await bcrypt.compare(currentPassword, result.rows[0].password_hash);
    if (!passwordMatch) {
      return res.status(401).json({ error: 'Nykyinen salasana on väärä' });
    }

    // Hashaa uusi salasana
    const saltRounds = 12;
    const newPasswordHash = await bcrypt.hash(newPassword, saltRounds);

    // Päivitä salasana
    await pool.query(
      'UPDATE users SET password_hash = $1, updated_at = CURRENT_TIMESTAMP WHERE id = $2',
      [newPasswordHash, req.user.userId]
    );

    res.json({ message: 'Salasana vaihdettu onnistuneesti!' });

  } catch (error) {
    console.error('Salasanan vaihtovirhe:', error);
    res.status(500).json({ error: 'Salasanan vaihto epäonnistui' });
  }
});

// Hae käyttäjän tilastot
router.get('/stats', authenticateToken, async (req, res) => {
  try {
    // Käyttäjän perustiedot
    const userResult = await pool.query(
      'SELECT created_at, subscription_active, subscription_end_date FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (userResult.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    const user = userResult.rows[0];
    const subscriptionActive = user.subscription_active && 
      (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

    // Kurssitilastot
    const courseStatsResult = await pool.query(`
      SELECT 
        COUNT(DISTINCT c.id) as total_courses,
        COUNT(DISTINCT CASE WHEN up.completed = true THEN c.id END) as completed_courses,
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT CASE WHEN up.completed = true THEN up.lesson_id END) as completed_lessons,
        AVG(up.quiz_score) as average_quiz_score
      FROM courses c
      LEFT JOIN lessons l ON c.id = l.course_id
      LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = $1
    `, [req.user.userId]);

    const stats = courseStatsResult.rows[0];

    // Viimeisimmät suoritukset
    const recentProgressResult = await pool.query(`
      SELECT 
        c.title as course_title,
        l.title as lesson_title,
        up.completed_at,
        up.quiz_score
      FROM user_progress up
      JOIN lessons l ON up.lesson_id = l.id
      JOIN courses c ON l.course_id = c.id
      WHERE up.user_id = $1 AND up.completed = true
      ORDER BY up.completed_at DESC
      LIMIT 10
    `, [req.user.userId]);

    // Kategoriatilastot
    const categoryStatsResult = await pool.query(`
      SELECT 
        c.category,
        COUNT(DISTINCT CASE WHEN up.completed = true THEN c.id END) as completed_courses,
        COUNT(DISTINCT c.id) as total_courses
      FROM courses c
      LEFT JOIN user_progress up ON c.id = up.course_id AND up.user_id = $1
      GROUP BY c.category
    `, [req.user.userId]);

    res.json({
      user: {
        memberSince: user.created_at,
        subscriptionActive,
        subscriptionEndDate: user.subscription_end_date
      },
      stats: {
        totalCourses: parseInt(stats.total_courses),
        completedCourses: parseInt(stats.completed_courses),
        totalLessons: parseInt(stats.total_lessons),
        completedLessons: parseInt(stats.completed_lessons),
        averageQuizScore: stats.average_quiz_score ? Math.round(stats.average_quiz_score) : 0,
        completionRate: stats.total_courses > 0 
          ? Math.round((stats.completed_courses / stats.total_courses) * 100) 
          : 0
      },
      recentProgress: recentProgressResult.rows.map(progress => ({
        courseTitle: progress.course_title,
        lessonTitle: progress.lesson_title,
        completedAt: progress.completed_at,
        quizScore: progress.quiz_score
      })),
      categoryStats: categoryStatsResult.rows.map(cat => ({
        category: cat.category,
        completedCourses: parseInt(cat.completed_courses),
        totalCourses: parseInt(cat.total_courses),
        completionRate: cat.total_courses > 0 
          ? Math.round((cat.completed_courses / cat.total_courses) * 100) 
          : 0
      }))
    });

  } catch (error) {
    console.error('Tilastojen hakuvirhe:', error);
    res.status(500).json({ error: 'Tilastojen haku epäonnistui' });
  }
});

// Poista käyttäjätili
router.delete('/account', authenticateToken, async (req, res) => {
  try {
    const { password } = req.body;

    if (!password) {
      return res.status(400).json({ error: 'Salasana vaaditaan tiliin poistamiseen' });
    }

    // Tarkista salasana
    const result = await pool.query(
      'SELECT password_hash FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    const passwordMatch = await bcrypt.compare(password, result.rows[0].password_hash);
    if (!passwordMatch) {
      return res.status(401).json({ error: 'Väärä salasana' });
    }

    // Poista käyttäjä (CASCADE poistaa myös liitokset)
    await pool.query('DELETE FROM users WHERE id = $1', [req.user.userId]);

    res.json({ message: 'Tili poistettu onnistuneesti!' });

  } catch (error) {
    console.error('Tilin poistovirhe:', error);
    res.status(500).json({ error: 'Tilin poistaminen epäonnistui' });
  }
});

module.exports = router;
