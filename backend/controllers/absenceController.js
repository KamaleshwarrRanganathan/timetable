const pool = require('../config/db');
const { createNotification } = require('./notificationController');

exports.markAbsent = async (req, res) => {
    const { teacher_id, date, reason } = req.body;

    try {
        // 1. Record the absence
        const [insertResult] = await pool.query(
            'INSERT INTO absences (teacher_id, absence_date, reason) VALUES (?, ?, ?)',
            [teacher_id, date, reason]
        );
        const absenceId = insertResult.insertId;

        // 2. Find periods the teacher was supposed to teach on this date based on day_of_week
        // Here we just use a dummy day string 'Monday' etc.
        const dayOfWeek = new Date(date).toLocaleDateString('en-US', { weekday: 'long' });

        const [periodsToCover] = await pool.query(
            'SELECT * FROM timetable WHERE teacher_id = ? AND day_of_week = ?',
            [teacher_id, dayOfWeek]
        );

        if (periodsToCover.length > 0) {
            // Pick a substitute teacher randomly who isn't already teaching at this time
            const [allTeachers] = await pool.query('SELECT id FROM users WHERE role = "teacher" AND id != ?', [teacher_id]);

            if (allTeachers.length > 0) {
                // Simplistic assignment: just pick the first available teacher from the pool
                // Real logic would join `timetable` and exclude those having classes at specific start_time/end_time
                const substitute_id = allTeachers[0].id;
                
                // Update absence record with substitute
                await pool.query('UPDATE absences SET substitute_teacher_id = ? WHERE id = ?', [substitute_id, absenceId]);

                // Notify original teacher
                await createNotification(teacher_id, 'Your absence for ' + date + ' has been registered. Substitute assigned.');

                // Notify substitute teacher
                await createNotification(substitute_id, 'You have been assigned as a substitute on ' + date + ' in place of another teacher.');

                return res.status(200).json({ message: 'Absence recorded and substitute assigned.', substitute_id });
            }
        }

        // Notify if no substitute found or no classes
        await createNotification(teacher_id, 'Absence registered for ' + date + '. No classes impacted or no substitute available.');
        res.status(200).json({ message: 'Absence recorded. No substitute needed/found.' });

    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error marking absence' });
    }
};

exports.getAbsences = async (req, res) => {
    try {
        const [absences] = await pool.query(`
            SELECT a.*, u1.name as teacher_name, u2.name as substitute_name
            FROM absences a
            JOIN users u1 ON a.teacher_id = u1.id
            LEFT JOIN users u2 ON a.substitute_teacher_id = u2.id
            ORDER BY a.absence_date DESC
        `);
        res.json(absences);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Server error' });
    }
};
