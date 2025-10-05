const { Pool } = require('pg');
const fs = require('fs');
const path = require('path');
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
    console.log('🚀 Aloitetaan tietokannan asennus...');
    
    // Lue schema-tiedosto
    const schemaPath = path.join(__dirname, '..', 'database', 'schema.sql');
    const schema = fs.readFileSync(schemaPath, 'utf8');
    
    // Suorita schema
    console.log('📝 Luodaan taulut ja indeksit...');
    await pool.query(schema);
    
    console.log('✅ Tietokanta asennettu onnistuneesti!');
    console.log('');
    console.log('🎓 Opettajakäyttäjä luotu:');
    console.log('   Sähköposti: arttuz311@gmail.com');
    console.log('   Salasana: opettaja123');
    console.log('   Rooli: teacher');
    console.log('');
    console.log('💡 Voit nyt kirjautua sisään opettajana ja aloittaa kurssien hallinnan!');
    
  } catch (error) {
    console.error('❌ Tietokannan asennus epäonnistui:', error.message);
    process.exit(1);
  } finally {
    await pool.end();
  }
}

setupDatabase();
