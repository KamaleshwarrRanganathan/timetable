const pool = require('../config/db');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');

exports.login = async (req, res) => {
    const { username, password } = req.body;

    try {
        const [users] = await pool.execute('SELECT * FROM users WHERE username = ?', [username]);

        if (users.length === 0) {
            return res.status(401).json({ message: 'Invalid credentials' });
        }

        const user = users[0];

        // For demo purposes, if password is not hashed in DB, direct comparison or hash mapping
        // Here we assume passwords are plain text for simplicity in college project if bcrypt fails, 
        // but let's try bcrypt first.
        let isMatch = false;
        try {
            isMatch = await bcrypt.compare(password, user.password);
        } catch (e) {
             // Fallback if not hashed
             isMatch = password === user.password;
        }

        if (!isMatch) {
            return res.status(401).json({ message: 'Invalid credentials' });
        }

        const payload = {
            id: user.id,
            role: user.role,
            name: user.name
        };

        const token = jwt.sign(payload, process.env.JWT_SECRET || 'secret', { expiresIn: '1d' });

        res.json({
            token,
            user: {
                id: user.id,
                username: user.username,
                role: user.role,
                name: user.name
            }
        });

    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Server error' });
    }
};

exports.register = async (req, res) => { /* Admin creates users instead */ };
