const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const morgan = require('morgan');
const path = require('path');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(helmet());
app.use(cors({
  origin: process.env.FRONTEND_URL || 'http://localhost:3001',
  credentials: true
}));
app.use(morgan('combined'));
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/courses', require('./routes/courses'));
app.use('/api/lessons', require('./routes/lessons'));
app.use('/api/quiz', require('./routes/quiz'));
app.use('/api/subscription', require('./routes/subscription'));
app.use('/api/user', require('./routes/user'));
app.use('/api/teacher', require('./routes/teacher'));

// Health check
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    message: 'DigiTaidot kuntoon! API is running',
    timestamp: new Date().toISOString()
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({ 
    error: 'Jotain meni pieleen!', 
    message: process.env.NODE_ENV === 'development' ? err.message : 'Sisäinen palvelinvirhe'
  });
});

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Sivua ei löytynyt' });
});

app.listen(PORT, () => {
  console.log(`🚀 DigiTaidot kuntoon! API käynnissä portissa ${PORT}`);
  console.log(`🌐 Ympäristö: ${process.env.NODE_ENV || 'development'}`);
  console.log(`💰 Tilaushinta: ${(process.env.SUBSCRIPTION_PRICE / 100).toFixed(2)}€/kk`);
});
