const express = require('express');
const router = express.Router();
const adminController = require('../controllers/adminController');

// All routes here should be protected by an 'isAdmin' middleware in a real app
// For the mini project, we'll keep it simple or assume frontend sends a valid admin token

router.post('/teachers', adminController.addTeacher);
router.get('/teachers', adminController.getTeachers);

router.post('/subjects', adminController.addSubject);
router.get('/subjects', adminController.getSubjects);

router.post('/classrooms', adminController.addClassroom);
router.get('/classrooms', adminController.getClassrooms);

router.post('/classes', adminController.addClass);
router.get('/classes', adminController.getClasses);

module.exports = router;
