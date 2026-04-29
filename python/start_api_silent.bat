@echo off
REM ============================================================
REM  start_api_silent.bat
REM  Menjalankan DEMAKAI Python API sebagai proses terpisah
REM  menggunakan PowerShell Start-Process agar tetap berjalan
REM  bahkan setelah Task Scheduler menutup script ini.
REM ============================================================

set LOG=D:\magang_bps\backend-demakai\python\api.log
set APIDIR=D:\magang_bps\backend-demakai\python

REM Matikan proses lama di port 8000 jika ada
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000 ^| findstr LISTEN 2^>nul') do (
    taskkill /PID %%a /F >nul 2>&1
)

REM Catat timestamp start ke log
echo [%DATE% %TIME%] DEMAKAI API starting... >> %LOG%

REM Gunakan PowerShell Start-Process untuk detach proses sepenuhnya
REM -WindowStyle Hidden = tidak ada jendela CMD yang muncul
REM -WorkingDirectory   = pastikan path Python modules ditemukan
powershell -Command "Start-Process -FilePath 'python' -ArgumentList '-m uvicorn api:app --host 127.0.0.1 --port 8000' -WorkingDirectory '%APIDIR%' -WindowStyle Hidden"
