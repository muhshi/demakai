@echo off
REM ============================================================
REM  register_task_scheduler.bat
REM  Daftarkan DEMAKAI API ke Windows Task Scheduler
REM  Sehingga API autostart setiap kali user login Windows
REM
REM  CARA PAKAI:
REM    Klik kanan file ini → "Run as administrator"
REM ============================================================

echo =====================================================
echo   DEMAKAI API - Task Scheduler Setup
echo =====================================================
echo.

REM Cek apakah dijalankan sebagai Administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo [ERROR] Harus dijalankan sebagai Administrator!
    echo Klik kanan file ini dan pilih "Run as administrator"
    pause
    exit /b 1
)

set TASK_NAME=DEMAKAI-Python-API
set SCRIPT_PATH=D:\magang_bps\backend-demakai\python\start_api_silent.bat

REM Hapus task lama jika ada
schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1

REM Daftarkan task baru
schtasks /create ^
  /tn "%TASK_NAME%" ^
  /tr "\"%SCRIPT_PATH%\"" ^
  /sc ONLOGON ^
  /rl HIGHEST ^
  /f

if %errorLevel% EQU 0 (
    echo.
    echo [OK] Task berhasil didaftarkan!
    echo      Nama Task  : %TASK_NAME%
    echo      Script     : %SCRIPT_PATH%
    echo      Trigger    : Setiap user login Windows
    echo.
    echo Jalankan sekarang? (tanpa restart dulu)
    choice /c YN /m "Jalankan API sekarang"
    if errorlevel 1 if not errorlevel 2 (
        schtasks /run /tn "%TASK_NAME%"
        echo [OK] API sedang dijalankan...
        timeout /t 3 >nul
        echo Cek status: http://127.0.0.1:8000/health
    )
) else (
    echo [ERROR] Gagal mendaftarkan task. Coba jalankan sebagai Administrator.
)

echo.
pause
