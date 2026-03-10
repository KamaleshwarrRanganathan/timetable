const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');
const bcrypt = require('bcryptjs');
require('dotenv').config();

async function initDB() {
    try {
        console.log('Connecting to MySQL...');
        // First connect without database to create it if it doesn't exist
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST || 'localhost',
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || ''
        });

        const dbName = process.env.DB_NAME || 'scheduling_system';
        console.log(`Creating database ${dbName} if it doesn't exist...`);
        await connection.query(`CREATE DATABASE IF NOT EXISTS \`${dbName}\``);
        await connection.query(`USE \`${dbName}\``);

        console.log('Reading schema.sql...');
        const schemaPath = path.join(__dirname, '..', 'database', 'schema.sql');
        const schema = fs.readFileSync(schemaPath, 'utf8');

        // Split queries by semicolon and execute them
        const queries = schema.split(';').filter(q => q.trim());
        for (let query of queries) {
            if (query.trim()) {
                await connection.query(query);
            }
        }
        console.log('Schema imported successfully.');

        // Insert default users (Admin, Teacher, HOD, Student)
        console.log('Seeding default users...');
        const passwordHash = await bcrypt.hash('123', 10);
        
        const defaultUsers = [
            ['admin', passwordHash, 'admin', 'Super Admin', 'admin@college.edu'],
            ['teacher1', passwordHash, 'teacher', 'John Doe', 'john@college.edu'],
            ['hod1', passwordHash, 'hod', 'Jane Smith', 'hod@college.edu'],
            ['student1', passwordHash, 'student', 'Student 1', 'student@college.edu']
        ];

        for (const user of defaultUsers) {
            try {
                await connection.query(
                    'INSERT IGNORE INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)',
                    user
                );
            } catch (err) {
                // Ignore duplicates
                console.log(`Skipped adding user ${user[0]} (might already exist)`);
            }
        }

        console.log('Database initialization complete!');
        process.exit(0);

    } catch (error) {
        console.error('Database initialization failed:', error);
        process.exit(1);
    }
}

initDB();
