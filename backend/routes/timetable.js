const express = require('express');
const router = express.Router();
const timetableController = require('../controllers/timetableController');

router.post('/generate', timetableController.generateTimetable);
router.get('/', timetableController.getTimetable);

module.exports = router;
