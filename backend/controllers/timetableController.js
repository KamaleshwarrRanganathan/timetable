const pool = require('../config/db');

// Dummy algorithm to generate timetable
exports.generateTimetable = async (req, res) => {
    try {
        // 1. Fetch data
        const [classes] = await pool.query('SELECT * FROM classes');
        const [subjects] = await pool.query('SELECT * FROM subjects');
        const [teachers] = await pool.query('SELECT id FROM users WHERE role="teacher"');
        const [classrooms] = await pool.query('SELECT * FROM classrooms');

        if (classes.length === 0 || subjects.length === 0 || teachers.length === 0 || classrooms.length === 0) {
            return res.status(400).json({ message: 'Missing required data (classes, subjects, teachers, classrooms) to generate timetable.' });
        }

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        const timeSlots = [
            { start: '09:00:00', end: '10:00:00' },
            { start: '10:00:00', end: '11:00:00' },
            { start: '11:15:00', end: '12:15:00' } // Example 3 slots per day
        ];

        // Clear existing timetable for a fresh generation
        await pool.query('TRUNCATE TABLE timetable');

        let insertPromises = [];

        // Very basic simple assignment:
        for (let c of classes) {
            for (let day of days) {
                for (let i = 0; i < timeSlots.length; i++) {
                    // Randomly pick subject, teacher, classroom for demonstration
                    // In a real conflict-free system, you'd check `timetable` array if teacher/classroom is already booked for this day and slot.
                    let subj = subjects[i % subjects.length];
                    let teacher = teachers[i % teachers.length];
                    let room = classrooms[i % classrooms.length];
                    let slot = timeSlots[i];

                    insertPromises.push(
                        pool.query(
                            'INSERT INTO timetable (class_id, subject_id, teacher_id, classroom_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)',
                            [c.id, subj.id, teacher.id, room.id, day, slot.start, slot.end]
                        )
                    );
                }
            }
        }

        await Promise.all(insertPromises);
        res.status(200).json({ message: 'Timetable generated successfully!' });

    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error generating timetable' });
    }
};

exports.getTimetable = async (req, res) => {
    const { role, user_id, class_id } = req.query; // Filters
    
    try {
        let query = `
            SELECT t.*, c.name as class_name, s.name as subject_name, u.name as teacher_name, cr.name as classroom_name 
            FROM timetable t
            JOIN classes c ON t.class_id = c.id
            JOIN subjects s ON t.subject_id = s.id
            JOIN users u ON t.teacher_id = u.id
            JOIN classrooms cr ON t.classroom_id = cr.id
        `;
        let queryParams = [];

        if (role === 'teacher' && user_id) {
            query += ' WHERE t.teacher_id = ?';
            queryParams.push(user_id);
        } else if (role === 'student' && class_id) {
            query += ' WHERE t.class_id = ?';
            queryParams.push(class_id);
        }

        query += ' ORDER BY FIELD(t.day_of_week, "Monday", "Tuesday", "Wednesday", "Thursday", "Friday"), t.start_time';

        const [timetable] = await pool.query(query, queryParams);
        res.json(timetable);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Server error' });
    }
};
