require('dotenv').config();
const express = require('express');
const cors = require('cors');

const app = express();
const PORT = process.env.PORT || 5000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Routes
app.use('/api/auth', require('./routes/auth'));
app.use('/api/admin', require('./routes/admin'));
app.use('/api/timetable', require('./routes/timetable'));
app.use('/api/notifications', require('./routes/notifications'));
app.use('/api/absences', require('./routes/absences'));
app.use('/api/student', require('./routes/student'));

app.get('/', (req, res) => {
    res.send('Automated Scheduling System API is running...');
});

app.listen(PORT, () => {
    console.log(`Server is running on port ${PORT}`);
});
