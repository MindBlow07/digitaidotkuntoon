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

// Hae oppitunnin quiz-kysymykset
router.get('/lesson/:lessonId', authenticateToken, async (req, res) => {
  try {
    const lessonId = parseInt(req.params.lessonId);

    // Tarkista että oppitunti on olemassa ja käyttäjällä on pääsy
    const lessonResult = await pool.query(`
      SELECT l.id, l.course_id, c.is_premium
      FROM lessons l
      JOIN courses c ON l.course_id = c.id
      WHERE l.id = $1
    `, [lessonId]);

    if (lessonResult.rows.length === 0) {
      return res.status(404).json({ error: 'Oppituntia ei löytynyt' });
    }

    const lesson = lessonResult.rows[0];

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
        return res.status(403).json({ error: 'Tämä quiz vaatii aktiivisen tilauksen' });
      }
    }

    // Hae quiz-kysymykset
    const questionsResult = await pool.query(
      'SELECT id, question, options, correct_answer, explanation FROM quiz_questions WHERE lesson_id = $1 ORDER BY id',
      [lessonId]
    );

    // Poista oikeat vastaukset ennen lähettämistä
    const questions = questionsResult.rows.map(q => ({
      id: q.id,
      question: q.question,
      options: q.options,
      explanation: q.explanation
    }));

    res.json({ questions });

  } catch (error) {
    console.error('Quiz-kysymysten hakuvirhe:', error);
    res.status(500).json({ error: 'Quiz-kysymysten haku epäonnistui' });
  }
});

// Lähetä quiz-vastaukset ja laske pisteet
router.post('/lesson/:lessonId/submit', authenticateToken, async (req, res) => {
  try {
    const lessonId = parseInt(req.params.lessonId);
    const { answers } = req.body; // [{ questionId, selectedAnswer }]

    if (!answers || !Array.isArray(answers)) {
      return res.status(400).json({ error: 'Vastaukset puuttuvat' });
    }

    // Hae oikeat vastaukset
    const questionsResult = await pool.query(
      'SELECT id, correct_answer FROM quiz_questions WHERE lesson_id = $1',
      [lessonId]
    );

    if (questionsResult.rows.length === 0) {
      return res.status(404).json({ error: 'Quiz-kysymyksiä ei löytynyt' });
    }

    // Laske pisteet
    let correctAnswers = 0;
    const results = [];

    questionsResult.rows.forEach(question => {
      const userAnswer = answers.find(a => a.questionId === question.id);
      const isCorrect = userAnswer && userAnswer.selectedAnswer === question.correct_answer;
      
      if (isCorrect) {
        correctAnswers++;
      }

      results.push({
        questionId: question.id,
        correctAnswer: question.correct_answer,
        userAnswer: userAnswer ? userAnswer.selectedAnswer : null,
        isCorrect
      });
    });

    const totalQuestions = questionsResult.rows.length;
    const score = Math.round((correctAnswers / totalQuestions) * 100);

    // Tallenna tulos tietokantaan
    const lessonResult = await pool.query(
      'SELECT course_id FROM lessons WHERE id = $1',
      [lessonId]
    );

    if (lessonResult.rows.length > 0) {
      const courseId = lessonResult.rows[0].course_id;

      // Päivitä tai luo edistymismerkintä
      await pool.query(`
        INSERT INTO user_progress (user_id, course_id, lesson_id, quiz_score, completed, completed_at)
        VALUES ($1, $2, $3, $4, true, CURRENT_TIMESTAMP)
        ON CONFLICT (user_id, lesson_id)
        DO UPDATE SET 
          quiz_score = EXCLUDED.quiz_score,
          completed = true,
          completed_at = CURRENT_TIMESTAMP
      `, [req.user.userId, courseId, lessonId, score]);
    }

    res.json({
      score,
      correctAnswers,
      totalQuestions,
      results,
      message: score >= 70 ? 'Hienoa! Suoritit quizin!' : 'Harjoittele vielä vähän!'
    });

  } catch (error) {
    console.error('Quiz-lähetyksen virhe:', error);
    res.status(500).json({ error: 'Quiz-lähetys epäonnistui' });
  }
});

// Hae kurssin loppukoe
router.get('/course/:courseId/final', authenticateToken, async (req, res) => {
  try {
    const courseId = parseInt(req.params.courseId);

    // Tarkista kurssin pääsy
    const courseResult = await pool.query(
      'SELECT is_premium FROM courses WHERE id = $1',
      [courseId]
    );

    if (courseResult.rows.length === 0) {
      return res.status(404).json({ error: 'Kurssia ei löytynyt' });
    }

    const course = courseResult.rows[0];

    if (course.is_premium) {
      const userResult = await pool.query(
        'SELECT subscription_active, subscription_end_date FROM users WHERE id = $1',
        [req.user.userId]
      );

      const user = userResult.rows[0];
      const hasActiveSubscription = user.subscription_active && 
        (!user.subscription_end_date || new Date(user.subscription_end_date) > new Date());

      if (!hasActiveSubscription) {
        return res.status(403).json({ error: 'Loppukoe vaatii aktiivisen tilauksen' });
      }
    }

    // Hae kurssin kaikki quiz-kysymykset
    const questionsResult = await pool.query(`
      SELECT qq.id, qq.question, qq.options, qq.correct_answer, qq.explanation
      FROM quiz_questions qq
      JOIN lessons l ON qq.lesson_id = l.id
      WHERE l.course_id = $1
      ORDER BY RANDOM()
      LIMIT 20
    `, [courseId]);

    if (questionsResult.rows.length === 0) {
      return res.status(404).json({ error: 'Loppukoe-kysymyksiä ei löytynyt' });
    }

    // Poista oikeat vastaukset ennen lähettämistä
    const questions = questionsResult.rows.map(q => ({
      id: q.id,
      question: q.question,
      options: q.options,
      explanation: q.explanation
    }));

    res.json({ 
      questions,
      totalQuestions: questions.length,
      timeLimit: 1800 // 30 minuuttia sekunteina
    });

  } catch (error) {
    console.error('Loppukoe-hakuvirhe:', error);
    res.status(500).json({ error: 'Loppukoe-haku epäonnistui' });
  }
});

// Lähetä loppukoe-vastaukset
router.post('/course/:courseId/final/submit', authenticateToken, async (req, res) => {
  try {
    const courseId = parseInt(req.params.courseId);
    const { answers, timeSpent } = req.body;

    if (!answers || !Array.isArray(answers)) {
      return res.status(400).json({ error: 'Vastaukset puuttuvat' });
    }

    // Hae oikeat vastaukset
    const questionsResult = await pool.query(`
      SELECT qq.id, qq.correct_answer
      FROM quiz_questions qq
      JOIN lessons l ON qq.lesson_id = l.id
      WHERE l.course_id = $1
    `, [courseId]);

    if (questionsResult.rows.length === 0) {
      return res.status(404).json({ error: 'Loppukoe-kysymyksiä ei löytynyt' });
    }

    // Laske pisteet
    let correctAnswers = 0;
    const results = [];

    questionsResult.rows.forEach(question => {
      const userAnswer = answers.find(a => a.questionId === question.id);
      const isCorrect = userAnswer && userAnswer.selectedAnswer === question.correct_answer;
      
      if (isCorrect) {
        correctAnswers++;
      }

      results.push({
        questionId: question.id,
        correctAnswer: question.correct_answer,
        userAnswer: userAnswer ? userAnswer.selectedAnswer : null,
        isCorrect
      });
    });

    const totalQuestions = questionsResult.rows.length;
    const score = Math.round((correctAnswers / totalQuestions) * 100);

    // Tallenna loppukoe-tulos
    await pool.query(`
      INSERT INTO user_progress (user_id, course_id, lesson_id, quiz_score, completed, completed_at)
      VALUES ($1, $2, NULL, $3, true, CURRENT_TIMESTAMP)
      ON CONFLICT (user_id, course_id, lesson_id)
      DO UPDATE SET 
        quiz_score = EXCLUDED.quiz_score,
        completed = true,
        completed_at = CURRENT_TIMESTAMP
    `, [req.user.userId, courseId, score]);

    // Tarkista onko kurssi suoritettu (70% pisteet)
    const courseCompleted = score >= 70;

    res.json({
      score,
      correctAnswers,
      totalQuestions,
      timeSpent,
      courseCompleted,
      results,
      message: courseCompleted 
        ? 'Onnittelut! Suoritit kurssin!' 
        : 'Kurssi ei vielä täytä suorittamiskriteereitä (70% pisteet)'
    });

  } catch (error) {
    console.error('Loppukoe-lähetyksen virhe:', error);
    res.status(500).json({ error: 'Loppukoe-lähetys epäonnistui' });
  }
});

module.exports = router;
