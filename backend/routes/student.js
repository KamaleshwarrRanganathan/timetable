const express = require('express');
const router = express.Router();
const studentController = require('../controllers/studentController');

router.get('/profile', studentController.getProfile);
router.put('/profile', studentController.updateProfile);

router.get('/results', studentController.getResults);
router.get('/exam-timetable', studentController.getExamTimetable);

router.get('/od-requests', studentController.getODRequests);
router.post('/od-requests', studentController.createODRequest);
router.put('/od-requests/:id/:action', studentController.actionODRequest);

router.get('/absences', studentController.getAbsences);
router.post('/absences', studentController.createAbsence);

module.exports = router;
