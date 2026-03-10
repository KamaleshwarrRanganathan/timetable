const pool = require('../config/db');
const bcrypt = require('bcryptjs');

// --- Users (Teachers/Students) Management ---
exports.addTeacher = async (req, res) => {
    const { username, password, name, email } = req.body;
    try {
        const hashedPassword = await bcrypt.hash(password, 10);
        const [result] = await pool.execute(
            'INSERT INTO users (username, password, role, name, email) VALUES (?, ?, ?, ?, ?)',
            [username, hashedPassword, 'teacher', name, email]
        );
        res.status(201).json({ message: 'Teacher added successfully', id: result.insertId });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error adding teacher' });
    }
};

exports.getTeachers = async (req, res) => {
    try {
        const [teachers] = await pool.execute('SELECT id, username, name, email FROM users WHERE role = "teacher"');
        res.json(teachers);
    } catch (err) {
        res.status(500).json({ message: 'Server error' });
    }
};

// --- Subjects Management ---
exports.addSubject = async (req, res) => {
    const { name, code } = req.body;
    try {
        const [result] = await pool.execute(
            'INSERT INTO subjects (name, code) VALUES (?, ?)',
            [name, code]
        );
        res.status(201).json({ message: 'Subject added successfully', id: result.insertId });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error adding subject' });
    }
};

exports.getSubjects = async (req, res) => {
    try {
        const [subjects] = await pool.execute('SELECT * FROM subjects');
        res.json(subjects);
    } catch (err) {
        res.status(500).json({ message: 'Server error' });
    }
};

// --- Classrooms Management ---
exports.addClassroom = async (req, res) => {
    const { name, capacity } = req.body;
    try {
        const [result] = await pool.execute(
            'INSERT INTO classrooms (name, capacity) VALUES (?, ?)',
            [name, capacity]
        );
        res.status(201).json({ message: 'Classroom added successfully', id: result.insertId });
    } catch (err) {
        res.status(500).json({ message: 'Error adding classroom' });
    }
};

exports.getClassrooms = async (req, res) => {
    try {
        const [classrooms] = await pool.execute('SELECT * FROM classrooms');
        res.json(classrooms);
    } catch (err) {
        res.status(500).json({ message: 'Server error' });
    }
};

// --- Classes Management ---
exports.addClass = async (req, res) => {
    const { name } = req.body;
    try {
        const [result] = await pool.execute(
            'INSERT INTO classes (name) VALUES (?)',
            [name]
        );
        res.status(201).json({ message: 'Class added successfully', id: result.insertId });
    } catch (err) {
        res.status(500).json({ message: 'Error adding class' });
    }
};

exports.getClasses = async (req, res) => {
    try {
        const [classes] = await pool.execute('SELECT * FROM classes');
        res.json(classes);
    } catch (err) {
        res.status(500).json({ message: 'Server error' });
    }
};
