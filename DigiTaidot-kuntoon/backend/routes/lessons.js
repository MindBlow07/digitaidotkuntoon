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

// Merkitse oppitunti suoritetuksi
router.post('/:id/complete', authenticateToken, async (req, res) => {
  try {
    const lessonId = parseInt(req.params.id);

    // Tarkista että oppitunti on olemassa
    const lessonResult = await pool.query(
      'SELECT id, course_id FROM lessons WHERE id = $1',
      [lessonId]
    );

    if (lessonResult.rows.length === 0) {
      return res.status(404).json({ error: 'Oppituntia ei löytynyt' });
    }

    const lesson = lessonResult.rows[0];

    // Tarkista kurssin pääsy
    const courseResult = await pool.query(
      'SELECT is_premium FROM courses WHERE id = $1',
      [lesson.course_id]
    );

    if (courseResult.rows.length === 0) {
      return res.status(404).json({ error: 'Kurssia ei löytynyt' });
    }

    const course = courseResult.rows[0];

    if (course.is_premium) {
      // Tarkista käyttäjän tilaus
      const userResult = await pool.query(
        'SELECT subscription_active, subscription_end_date FROM users WHERE id = $1',
        [req.user.userId]
      );

      const user = userResult.rows[0];
      const hasActiveSubscription = user.subscription_active && 
        (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

      if (!hasActiveSubscription) {
        return res.status(403).json({ error: 'Tämä oppitunti vaatii aktiivisen tilauksen' });
      }
    }

    // Tarkista onko oppitunti jo suoritettu
    const existingProgress = await pool.query(
      'SELECT id FROM user_progress WHERE user_id = $1 AND lesson_id = $2',
      [req.user.userId, lessonId]
    );

    if (existingProgress.rows.length > 0) {
      // Päivitä olemassa oleva merkintä
      await pool.query(
        'UPDATE user_progress SET completed = true, completed_at = CURRENT_TIMESTAMP WHERE user_id = $1 AND lesson_id = $2',
        [req.user.userId, lessonId]
      );
    } else {
      // Luo uusi merkintä
      await pool.query(
        'INSERT INTO user_progress (user_id, course_id, lesson_id, completed, completed_at) VALUES ($1, $2, $3, true, CURRENT_TIMESTAMP)',
        [req.user.userId, lesson.course_id, lessonId]
      );
    }

    res.json({ message: 'Oppitunti merkitty suoritetuksi!' });

  } catch (error) {
    console.error('Oppitunnin merkintävirhe:', error);
    res.status(500).json({ error: 'Oppitunnin merkintä epäonnistui' });
  }
});

// Hae oppitunnin tiedot
router.get('/:id', authenticateToken, async (req, res) => {
  try {
    const lessonId = parseInt(req.params.id);

    // Hae oppitunnin tiedot kurssin kanssa
    const result = await pool.query(`
      SELECT 
        l.id,
        l.title,
        l.content,
        l.video_url,
        l.order_index,
        c.id as course_id,
        c.title as course_title,
        c.is_premium,
        c.category
      FROM lessons l
      JOIN courses c ON l.course_id = c.id
      WHERE l.id = $1
    `, [lessonId]);

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Oppituntia ei löytynyt' });
    }

    const lesson = result.rows[0];

    // Tarkista pääsy kurssiin
    if (lesson.is_premium) {
      const userResult = await pool.query(
        'SELECT subscription_active, subscription_end_date FROM users WHERE id = $1',
        [req.user.userId]
      );

      const user = userResult.rows[0];
      const hasActiveSubscription = user.subscription_active && 
        (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

      if (!hasActiveSubscription) {
        return res.status(403).json({ 
          error: 'Tämä oppitunti vaatii aktiivisen tilauksen',
          requiresSubscription: true,
          subscriptionPrice: (process.env.SUBSCRIPTION_PRICE / 100).toFixed(2)
        });
      }
    }

    // Hae käyttäjän edistyminen
    const progressResult = await pool.query(
      'SELECT completed, completed_at, quiz_score FROM user_progress WHERE user_id = $1 AND lesson_id = $2',
      [req.user.userId, lessonId]
    );

    const progress = progressResult.rows[0] || { completed: false, quiz_score: 0 };

    res.json({
      lesson: {
        id: lesson.id,
        title: lesson.title,
        content: lesson.content,
        videoUrl: lesson.video_url,
        orderIndex: lesson.order_index,
        courseId: lesson.course_id,
        courseTitle: lesson.course_title,
        category: lesson.category
      },
      progress: {
        completed: progress.completed,
        completedAt: progress.completed_at,
        quizScore: progress.quiz_score || 0
      }
    });

  } catch (error) {
    console.error('Oppitunnin hakuvirhe:', error);
    res.status(500).json({ error: 'Oppitunnin haku epäonnistui' });
  }
});

// Hae seuraava/edellinen oppitunti
router.get('/:id/navigation', authenticateToken, async (req, res) => {
  try {
    const lessonId = parseInt(req.params.id);

    // Hae nykyinen oppitunti ja kurssi
    const currentResult = await pool.query(`
      SELECT l.order_index, l.course_id, c.title as course_title
      FROM lessons l
      JOIN courses c ON l.course_id = c.id
      WHERE l.id = $1
    `, [lessonId]);

    if (currentResult.rows.length === 0) {
      return res.status(404).json({ error: 'Oppituntia ei löytynyt' });
    }

    const current = currentResult.rows[0];

    // Hae edellinen oppitunti
    const previousResult = await pool.query(
      'SELECT id, title FROM lessons WHERE course_id = $1 AND order_index < $2 ORDER BY order_index DESC LIMIT 1',
      [current.course_id, current.order_index]
    );

    // Hae seuraava oppitunti
    const nextResult = await pool.query(
      'SELECT id, title FROM lessons WHERE course_id = $1 AND order_index > $2 ORDER BY order_index ASC LIMIT 1',
      [current.course_id, current.order_index]
    );

    res.json({
      previous: previousResult.rows[0] || null,
      next: nextResult.rows[0] || null,
      courseTitle: current.course_title
    });

  } catch (error) {
    console.error('Navigointivirhe:', error);
    res.status(500).json({ error: 'Navigointi epäonnistui' });
  }
});

module.exports = router;
