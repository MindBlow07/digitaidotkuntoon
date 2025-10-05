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

// Tarkista että käyttäjä on opettaja
const requireTeacher = async (req, res, next) => {
  try {
    const result = await pool.query(
      'SELECT role FROM users WHERE id = $1',
      [req.user.userId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Käyttäjää ei löytynyt' });
    }

    const user = result.rows[0];
    if (user.role !== 'teacher' && user.role !== 'admin') {
      return res.status(403).json({ error: 'Vain opettajat voivat käyttää tätä toimintoa' });
    }

    req.userRole = user.role;
    next();
  } catch (error) {
    console.error('Roolin tarkistusvirhe:', error);
    res.status(500).json({ error: 'Roolin tarkistus epäonnistui' });
  }
};

// Hae kaikki kurssit (opettajanäkymä)
router.get('/courses', authenticateToken, requireTeacher, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT 
        c.id,
        c.title,
        c.description,
        c.category,
        c.difficulty,
        c.duration_minutes,
        c.is_premium,
        c.created_at,
        COUNT(l.id) as lesson_count,
        COUNT(up.id) as student_count
      FROM courses c
      LEFT JOIN lessons l ON c.id = l.course_id
      LEFT JOIN user_progress up ON c.id = up.course_id
      GROUP BY c.id, c.title, c.description, c.category, c.difficulty, c.duration_minutes, c.is_premium, c.created_at
      ORDER BY c.created_at DESC
    `);

    const courses = result.rows.map(course => ({
      id: course.id,
      title: course.title,
      description: course.description,
      category: course.category,
      difficulty: course.difficulty,
      durationMinutes: course.duration_minutes,
      isPremium: course.is_premium,
      lessonCount: parseInt(course.lesson_count),
      studentCount: parseInt(course.student_count),
      createdAt: course.created_at
    }));

    res.json({ courses });

  } catch (error) {
    console.error('Kurssien hakuvirhe:', error);
    res.status(500).json({ error: 'Kurssien haku epäonnistui' });
  }
});

// Luo uusi kurssi
router.post('/courses', authenticateToken, requireTeacher, async (req, res) => {
  try {
    const { title, description, category, difficulty, durationMinutes, isPremium } = req.body;

    if (!title || !description || !category || !difficulty) {
      return res.status(400).json({ error: 'Otsikko, kuvaus, kategoria ja vaikeustaso ovat pakollisia' });
    }

    const result = await pool.query(
      'INSERT INTO courses (title, description, category, difficulty, duration_minutes, is_premium, created_by) VALUES ($1, $2, $3, $4, $5, $6, $7) RETURNING *',
      [title, description, category, difficulty, durationMinutes || 0, isPremium || false, req.user.userId]
    );

    const course = result.rows[0];

    res.status(201).json({
      message: 'Kurssi luotu onnistuneesti!',
      course: {
        id: course.id,
        title: course.title,
        description: course.description,
        category: course.category,
        difficulty: course.difficulty,
        durationMinutes: course.duration_minutes,
        isPremium: course.is_premium,
        createdAt: course.created_at
      }
    });

  } catch (error) {
    console.error('Kurssin luontivirhe:', error);
    res.status(500).json({ error: 'Kurssin luonti epäonnistui' });
  }
});

// Päivitä kurssi
router.put('/courses/:id', authenticateToken, requireTeacher, async (req, res) => {
  try {
    const courseId = parseInt(req.params.id);
    const { title, description, category, difficulty, durationMinutes, isPremium } = req.body;

    if (!title || !description || !category || !difficulty) {
      return res.status(400).json({ error: 'Otsikko, kuvaus, kategoria ja vaikeustaso ovat pakollisia' });
    }

    const result = await pool.query(
      'UPDATE courses SET title = $1, description = $2, category = $3, difficulty = $4, duration_minutes = $5, is_premium = $6, updated_at = CURRENT_TIMESTAMP WHERE id = $7 RETURNING *',
      [title, description, category, difficulty, durationMinutes || 0, isPremium || false, courseId]
    );

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Kurssia ei löytynyt' });
    }

    const course = result.rows[0];

    res.json({
      message: 'Kurssi päivitetty onnistuneesti!',
      course: {
        id: course.id,
        title: course.title,
        description: course.description,
        category: course.category,
        difficulty: course.difficulty,
        durationMinutes: course.duration_minutes,
        isPremium: course.is_premium,
        updatedAt: course.updated_at
      }
    });

  } catch (error) {
    console.error('Kurssin päivitysvirhe:', error);
    res.status(500).json({ error: 'Kurssin päivitys epäonnistui' });
  }
});

// Poista kurssi
router.delete('/courses/:id', authenticateToken, requireTeacher, async (req, res) => {
  try {
    const courseId = parseInt(req.params.id);

    const result = await pool.query('DELETE FROM courses WHERE id = $1 RETURNING id', [courseId]);

    if (result.rows.length === 0) {
      return res.status(404).json({ error: 'Kurssia ei löytynyt' });
    }

    res.json({ message: 'Kurssi poistettu onnistuneesti!' });

  } catch (error) {
    console.error('Kurssin poistovirhe:', error);
    res.status(500).json({ error: 'Kurssin poistaminen epäonnistui' });
  }
});

// Hae oppilaat
router.get('/students', authenticateToken, requireTeacher, async (req, res) => {
  try {
    const result = await pool.query(`
      SELECT 
        u.id,
        u.email,
        u.first_name,
        u.last_name,
        u.subscription_active,
        u.subscription_end_date,
        u.created_at,
        COUNT(DISTINCT up.course_id) as enrolled_courses,
        COUNT(DISTINCT CASE WHEN up.completed = true THEN up.lesson_id END) as completed_lessons,
        AVG(up.quiz_score) as average_score
      FROM users u
      LEFT JOIN user_progress up ON u.id = up.user_id
      WHERE u.role = 'student'
      GROUP BY u.id, u.email, u.first_name, u.last_name, u.subscription_active, u.subscription_end_date, u.created_at
      ORDER BY u.created_at DESC
    `);

    const students = result.rows.map(student => {
      const subscriptionActive = student.subscription_active && 
        (!student.subscription_end_date || new Date(student.subscription_end_date) > new Date());

      return {
        id: student.id,
        email: student.email,
        firstName: student.first_name,
        lastName: student.last_name,
        subscriptionActive,
        subscriptionEndDate: student.subscription_end_date,
        enrolledCourses: parseInt(student.enrolled_courses),
        completedLessons: parseInt(student.completed_lessons),
        averageScore: student.average_score ? Math.round(student.average_score) : 0,
        memberSince: student.created_at
      };
    });

    res.json({ students });

  } catch (error) {
    console.error('Oppilaiden hakuvirhe:', error);
    res.status(500).json({ error: 'Oppilaiden haku epäonnistui' });
  }
});

// Hae yksittäisen oppilaan tiedot
router.get('/students/:id', authenticateToken, requireTeacher, async (req, res) => {
  try {
    const studentId = parseInt(req.params.id);

    // Oppilaan perustiedot
    const studentResult = await pool.query(
      'SELECT id, email, first_name, last_name, subscription_active, subscription_end_date, created_at FROM users WHERE id = $1 AND role = $2',
      [studentId, 'student']
    );

    if (studentResult.rows.length === 0) {
      return res.status(404).json({ error: 'Oppilasta ei löytynyt' });
    }

    const student = studentResult.rows[0];
    const subscriptionActive = student.subscription_active && 
      (!student.subscription_end_date || new Date(student.subscription_end_date) > new Date());

    // Oppilaan kurssiedistyminen
    const progressResult = await pool.query(`
      SELECT 
        c.id,
        c.title,
        c.category,
        c.difficulty,
        c.is_premium,
        COUNT(l.id) as total_lessons,
        COUNT(CASE WHEN up.completed = true THEN 1 END) as completed_lessons,
        AVG(up.quiz_score) as average_score,
        MAX(up.completed_at) as last_activity
      FROM courses c
      LEFT JOIN lessons l ON c.id = l.course_id
      LEFT JOIN user_progress up ON l.id = up.lesson_id AND up.user_id = $1
      GROUP BY c.id, c.title, c.category, c.difficulty, c.is_premium
      ORDER BY last_activity DESC
    `, [studentId]);

    const courses = progressResult.rows.map(course => ({
      id: course.id,
      title: course.title,
      category: course.category,
      difficulty: course.difficulty,
      isPremium: course.is_premium,
      totalLessons: parseInt(course.total_lessons),
      completedLessons: parseInt(course.completed_lessons),
      progressPercentage: course.total_lessons > 0 
        ? Math.round((course.completed_lessons / course.total_lessons) * 100) 
        : 0,
      averageScore: course.average_score ? Math.round(course.average_score) : 0,
      lastActivity: course.last_activity
    }));

    res.json({
      student: {
        id: student.id,
        email: student.email,
        firstName: student.first_name,
        lastName: student.last_name,
        subscriptionActive,
        subscriptionEndDate: student.subscription_end_date,
        memberSince: student.created_at
      },
      courses
    });

  } catch (error) {
    console.error('Oppilaan tietojen hakuvirhe:', error);
    res.status(500).json({ error: 'Oppilaan tietojen haku epäonnistui' });
  }
});

// Hae tilastot
router.get('/stats', authenticateToken, requireTeacher, async (req, res) => {
  try {
    // Yleiset tilastot
    const statsResult = await pool.query(`
      SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
        (SELECT COUNT(*) FROM courses) as total_courses,
        (SELECT COUNT(*) FROM lessons) as total_lessons,
        (SELECT COUNT(*) FROM users WHERE subscription_active = true) as active_subscriptions,
        (SELECT COUNT(*) FROM user_progress WHERE completed = true) as completed_lessons
    `);

    const stats = statsResult.rows[0];

    // Kurssien suosio
    const popularCoursesResult = await pool.query(`
      SELECT 
        c.id,
        c.title,
        c.category,
        COUNT(DISTINCT up.user_id) as student_count,
        COUNT(CASE WHEN up.completed = true THEN 1 END) as completed_lessons,
        AVG(up.quiz_score) as average_score
      FROM courses c
      LEFT JOIN lessons l ON c.id = l.course_id
      LEFT JOIN user_progress up ON l.id = up.lesson_id
      GROUP BY c.id, c.title, c.category
      ORDER BY student_count DESC
      LIMIT 10
    `);

    const popularCourses = popularCoursesResult.rows.map(course => ({
      id: course.id,
      title: course.title,
      category: course.category,
      studentCount: parseInt(course.student_count),
      completedLessons: parseInt(course.completed_lessons),
      averageScore: course.average_score ? Math.round(course.average_score) : 0
    }));

    // Viimeisimmät rekisteröityneet oppilaat
    const recentStudentsResult = await pool.query(`
      SELECT id, email, first_name, last_name, created_at
      FROM users 
      WHERE role = 'student'
      ORDER BY created_at DESC
      LIMIT 5
    `);

    const recentStudents = recentStudentsResult.rows.map(student => ({
      id: student.id,
      email: student.email,
      firstName: student.first_name,
      lastName: student.last_name,
      joinedAt: student.created_at
    }));

    res.json({
      stats: {
        totalStudents: parseInt(stats.total_students),
        totalCourses: parseInt(stats.total_courses),
        totalLessons: parseInt(stats.total_lessons),
        activeSubscriptions: parseInt(stats.active_subscriptions),
        completedLessons: parseInt(stats.completed_lessons)
      },
      popularCourses,
      recentStudents
    });

  } catch (error) {
    console.error('Tilastojen hakuvirhe:', error);
    res.status(500).json({ error: 'Tilastojen haku epäonnistui' });
  }
});

module.exports = router;
