@echo off
echo =============================================
echo   DEMAKAI Python API Server
echo =============================================
echo.

REM Cek kalau port 8000 sudah dipakai, matikan dulu
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000 ^| findstr LISTEN') do (
    echo Port 8000 sudah dipakai, matikan proses lama...
    taskkill /PID %%a /F >nul 2>&1
)

echo Menjalankan server di http://127.0.0.1:8000
echo Jangan tutup jendela ini selama pakai browser!
echo.

cd /d d:\magang_bps\backend-demakai\python
uvicorn api:app --host 127.0.0.1 --port 8000

pause
