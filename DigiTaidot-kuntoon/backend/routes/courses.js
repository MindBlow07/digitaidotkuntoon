const express = require('express');
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

// Hae kaikki kurssit (julkiset + käyttäjän tilaus)
router.get('/', authenticateToken, async (req, res) => {
  try {
    // Tarkista käyttäjän tilaus
    const userResult = await pool.query(
      'SELECT subscription_active, subscription_end_date FROM users WHERE id = $1',
      [req.user.userId]
    );

    const user = userResult.rows[0];
    const hasActiveSubscription = user.subscription_active && 
      (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

    // Hae kurssit
    const result = await pool.query(
      'SELECT id, title, description, category, difficulty, duration_minutes, is_premium, created_at FROM courses ORDER BY created_at DESC'
    );

    const courses = result.rows.map(course => ({
      ...course,
      accessible: !course.is_premium || hasActiveSubscription
    }));

    res.json({
      courses,
      hasActiveSubscription,
      subscriptionPrice: (process.env.SUBSCRIPTION_PRICE / 100).toFixed(2)
    });

  } catch (error) {
    console.error('Kurssien hakuvirhe:', error);
    res.status(500).json({ error: 'Kurssien haku epäonnistui' });
  }
});

// Hae yksittäinen kurssi
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const courseId = parseInt(req.params.id);

    // Hae kurssi
    const courseResult = await pool.query(
      'SELECT id, title, description, category, difficulty, duration_minutes, is_premium FROM courses WHERE id = $1',
      [courseId]
    );

    if (courseResult.rows.length === 0) {
      return res.status(404).json({ error: 'Kurssia ei löytynyt' });
    }

    const course = courseResult.rows[0];

    // Tarkista käyttäjän tilaus
    const userResult = await pool.query(
      'SELECT subscription_active, subscription_end_date FROM users WHERE id = $1',
      [req.user.userId]
    );

    const user = userResult.rows[0];
    const hasActiveSubscription = user.subscription_active && 
      (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

    if (course.is_premium && !hasActiveSubscription) {
      return res.status(403).json({ 
        error: 'Tämä kurssi vaatii aktiivisen tilauksen',
        requiresSubscription: true,
        subscriptionPrice: (process.env.SUBSCRIPTION_PRICE / 100).toFixed(2)
      });
    }

    // Hae oppitunnit
    const lessonsResult = await pool.query(
      'SELECT id, title, content, video_url, order_index FROM lessons WHERE course_id = $1 ORDER BY order_index',
      [courseId]
    );

    // Hae käyttäjän edistyminen
    const progressResult = await pool.query(
      'SELECT lesson_id, completed, completed_at, quiz_score FROM user_progress WHERE user_id = $1 AND course_id = $2',
      [req.user.userId, courseId]
    );

    const progressMap = {};
    progressResult.rows.forEach(progress => {
      progressMap[progress.lesson_id] = {
        completed: progress.completed,
        completedAt: progress.completed_at,
        quizScore: progress.quiz_score
      };
    });

    const lessons = lessonsResult.rows.map(lesson => ({
      ...lesson,
      progress: progressMap[lesson.id] || { completed: false, quizScore: 0 }
    }));

    res.json({
      course: {
        ...course,
        accessible: true
      },
      lessons,
      hasActiveSubscription
    });

  } catch (error) {
    console.error('Kurssin hakuvirhe:', error);
    res.status(500).json({ error: 'Kurssin haku epäonnistui' });
  }
});

// Hae käyttäjän kurssien edistyminen
router.get('/progress/overview', authenticateToken, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT 
        c.id,
        c.title,
        c.category,
        c.duration_minutes,
        c.is_premium,
        COUNT(l.id) as total_lessons,
        COUNT(CASE WHEN up.completed = true THEN 1 END) as completed_lessons,
        AVG(up.quiz_score) as average_score
      FROM courses c
      LEFT JOIN lessons l ON c.id = l.course_id
      LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = $1
      GROUP BY c.id, c.title, c.category, c.duration_minutes, c.is_premium
      ORDER BY c.created_at DESC
    `, [req.user.userId]);

    const courses = result.rows.map(course => ({
      id: course.id,
      title: course.title,
      category: course.category,
      durationMinutes: course.duration_minutes,
      isPremium: course.is_premium,
      totalLessons: parseInt(course.total_lessons),
      completedLessons: parseInt(course.completed_lessons),
      progressPercentage: course.total_lessons > 0 
        ? Math.round((course.completed_lessons / course.total_lessons) * 100) 
        : 0,
      averageScore: course.average_score ? Math.round(course.average_score) : 0
    }));

    res.json({ courses });

  } catch (error) {
    console.error('Edistymisen hakuvirhe:', error);
    res.status(500).json({ error: 'Edistymisen haku epäonnistui' });
  }
});

module.exports = router;
