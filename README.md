# Automated Conflict-Free Scheduling System with Smart Notifications

This is a complete web application project for a college mini-project. 

## Technology Stack
- **Frontend**: HTML5, CSS3, Vanilla JavaScript (Fetch API)
- **Backend**: Node.js, Express.js
- **Database**: MySQL

## Prerequisites
- **Node.js**: Ensure Node.js is installed on your machine.
- **MySQL**: Ensure MySQL Server is running locally.

## Setup Instructions

1. **Database Setup**
   - Open MySQL Workbench / CLI.
   - Run the script inside `database/schema.sql` to create your tables.
   - Add a dummy Admin user to the `users` table via SQL for your first login:
     ```sql
     INSERT INTO users (username, password, role, name, email) 
     VALUES ('admin', 'admin123', 'admin', 'Super Admin', 'admin@college.edu');
     ```
     *(Note: The provided backend currently accepts plain text for "admin123" if bcrypt matching fails, as a fallback for easy college project setup)*.

2. **Backend Setup**
   - Navigate to the `backend` folder in your terminal: `cd backend`
   - Run `npm install` to install dependencies.
   - Modify `backend/.env` with your MySQL credentials (DB_USER, DB_PASSWORD, etc.).
   - Start the server:
     ```bash
     npm start
     ```
   - The API will run on `http://localhost:5000`

3. **Frontend Setup**
   - No build step is required for the frontend.
   - You can simply open `frontend/index.html` directly in your browser, or use a live server extension (like VSCode Live Server).
   - Log in using your Admin credentials created in step 1.

## Features Implemented
- **Admin Management**: Add Teachers, Subjects, Classrooms, Classes.
- **Automated Scheduling Algorithm**: Simple generator that fills the DB table and checks for constraints.
- **Teacher Absences & Substitutes**: Teachers can mark themselves absent, which triggers a basic algorithm to assign a free substitute.
- **Notification System**: Simulated browser polling that alerts teachers/students on schedule updates.
- **Mobile Friendly Interface**: Responsive modern UI built purely with CSS.

## Architecture
This project follows a 3-tier REST architecture:
- `frontend/`: The Presentation Layer. Connects to backend via `js/api.js` using REST interfaces.
- `backend/controllers/`: Application Logic / Business rules.
- `backend/routes/`: Route declarations.
- `backend/config/`: Data Layer config connecting to MySQL.
