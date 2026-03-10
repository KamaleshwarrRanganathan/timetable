const pool = require('../config/db');

exports.getProfile = async (req, res) => {
    try {
        const { user_id } = req.query;
        const [users] = await pool.query('SELECT id, username, role, name, email FROM users WHERE id = ?', [user_id]);
        if (users.length === 0) return res.status(404).json({ message: 'User not found' });
        
        let user = users[0];
        const [profiles] = await pool.query('SELECT phone, address, semester, arrears, fees FROM student_profiles WHERE user_id = ?', [user_id]);
        if (profiles.length > 0) {
            user = { ...user, ...profiles[0] };
        }
        res.json(user);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.updateProfile = async (req, res) => {
    try {
        const { user_id, phone, address, semester, arrears, fees } = req.body;
        // Check if profile exists
        const [profiles] = await pool.query('SELECT * FROM student_profiles WHERE user_id = ?', [user_id]);
        if (profiles.length > 0) {
            await pool.query('UPDATE student_profiles SET phone=?, address=?, semester=?, arrears=?, fees=? WHERE user_id=?',
                             [phone, address, semester, arrears, fees, user_id]);
        } else {
            await pool.query('INSERT INTO student_profiles (user_id, phone, address, semester, arrears, fees) VALUES (?, ?, ?, ?, ?, ?)',
                             [user_id, phone, address, semester, arrears, fees]);
        }
        res.json({ message: 'Profile updated' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getResults = async (req, res) => {
    try {
        const [results] = await pool.query('SELECT * FROM exam_results');
        res.json(results);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getExamTimetable = async (req, res) => {
    try {
        const [tt] = await pool.query('SELECT * FROM exam_timetable');
        res.json(tt);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getODRequests = async (req, res) => {
    try {
        const [reqs] = await pool.query(`
            SELECT od.id, od.student_id, od.reason, DATE_FORMAT(od.request_date, '%Y-%m-%d') as date, od.status, u.name as student_name
            FROM od_requests od
            JOIN users u ON od.student_id = u.id
        `);
        res.json(reqs);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.createODRequest = async (req, res) => {
    try {
        const { student_id, reason, date } = req.body;
        await pool.query('INSERT INTO od_requests (student_id, reason, request_date, status) VALUES (?, ?, ?, ?)', 
                           [student_id, reason, date, 'Pending']);
        
        // Find HOD to notify
        const [hods] = await pool.query("SELECT id FROM users WHERE role = 'hod'");
        if (hods.length > 0) {
            await pool.query('INSERT INTO notifications (user_id, message) VALUES (?, ?)',
                [hods[0].id, `OD Request from ${req.body.student_name}: ${reason}`]
            );
        }
        res.json({ message: 'OD Request submitted' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.actionODRequest = async (req, res) => {
    try {
        const { id, action } = req.params;
        const status = action === 'approve' ? 'Approved' : 'Rejected';
        await pool.query('UPDATE od_requests SET status = ? WHERE id = ?', [status, id]);
        
        // Get student id to notify
        const [reqs] = await pool.query('SELECT student_id FROM od_requests WHERE id = ?', [id]);
        if (reqs.length > 0) {
            await pool.query('INSERT INTO notifications (user_id, message) VALUES (?, ?)',
                [reqs[0].student_id, `Your OD Request was ${status} by HOD.`]
            );
        }
        res.json({ message: `OD Request ${status}` });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.getAbsences = async (req, res) => {
    try {
        const [abs] = await pool.query(`
            SELECT a.id, a.student_id, DATE_FORMAT(a.absence_date, '%Y-%m-%d') as date, a.reason, u.name as student_name
            FROM student_absences a
            JOIN users u ON a.student_id = u.id
        `);
        res.json(abs);
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};

exports.createAbsence = async (req, res) => {
    try {
        const { student_id, date, reason } = req.body;
        await pool.query('INSERT INTO student_absences (student_id, absence_date, reason) VALUES (?, ?, ?)',
                         [student_id, date, reason]);
        res.json({ message: 'Absence posted' });
    } catch (err) {
        res.status(500).json({ error: err.message });
    }
};
