@echo off
echo =========================================
echo  Automated Scheduling System Server
echo =========================================
echo.
echo Starting the local PHP development server...
echo.
echo Keep this window open! The magic is happening here.
echo Press Ctrl+C if you ever want to stop the server.
echo.
echo Your app will open in your browser automatically in 3 seconds...
timeout /t 3 /nobreak > nul

start http://localhost:8000/frontend/index.html

C:\xampp\php\php.exe -S 0.0.0.0:8000
