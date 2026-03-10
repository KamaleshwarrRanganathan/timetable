const express = require('express');
const router = express.Router();
const absenceController = require('../controllers/absenceController');

router.post('/', absenceController.markAbsent);
router.get('/', absenceController.getAbsences);

module.exports = router;
