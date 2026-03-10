const pool = require('../config/db');

exports.getNotifications = async (req, res) => {
    const { user_id } = req.query; // Would normally come from authenticate token
    
    if (!user_id) return res.status(400).json({ message: 'User ID required' });

    try {
        const [notifications] = await pool.query(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC',
            [user_id]
        );
        res.json(notifications);
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error fetching notifications' });
    }
};

exports.markAsRead = async (req, res) => {
    const { id } = req.params;
    try {
        await pool.query('UPDATE notifications SET is_read = TRUE WHERE id = ?', [id]);
        res.status(200).json({ message: 'Notification marked as read' });
    } catch (err) {
        console.error(err);
        res.status(500).json({ message: 'Error updating notification' });
    }
};

// Helper function to create notification internally
exports.createNotification = async (user_id, message) => {
    try {
        await pool.query(
            'INSERT INTO notifications (user_id, message) VALUES (?, ?)',
            [user_id, message]
        );
    } catch (err) {
        console.error('Failed to create notification:', err);
    }
};
