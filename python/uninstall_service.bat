@echo off
REM ============================================================
REM  uninstall_service.bat
REM  Hapus DEMAKAI API dari Windows Service (NSSM) atau
REM  hapus dari Task Scheduler
REM
REM  CARA PAKAI: Klik kanan → "Run as administrator"
REM ============================================================

echo =====================================================
echo   DEMAKAI API - Uninstall / Remove
echo =====================================================
echo.

net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo [ERROR] Harus dijalankan sebagai Administrator!
    pause
    exit /b 1
)

REM -- Hapus Windows Service (NSSM) --
set NSSM=C:\nssm\nssm.exe
set SERVICE_NAME=DEMAKAI-API
if exist "%NSSM%" (
    sc query "%SERVICE_NAME%" >nul 2>&1
    if %errorLevel% EQU 0 (
        echo [INFO] Menghapus Windows Service: %SERVICE_NAME%
        %NSSM% stop   "%SERVICE_NAME%" >nul 2>&1
        %NSSM% remove "%SERVICE_NAME%" confirm
        echo [OK] Service dihapus.
    ) else (
        echo [INFO] Service %SERVICE_NAME% tidak ditemukan.
    )
)

REM -- Hapus Task Scheduler --
set TASK_NAME=DEMAKAI-Python-API
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %errorLevel% EQU 0 (
    echo [INFO] Menghapus Task Scheduler: %TASK_NAME%
    schtasks /delete /tn "%TASK_NAME%" /f
    echo [OK] Task Scheduler dihapus.
) else (
    echo [INFO] Task %TASK_NAME% tidak ditemukan.
)

REM -- Matikan proses uvicorn yang mungkin masih berjalan --
echo [INFO] Menghentikan proses API yang berjalan...
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000 ^| findstr LISTEN 2^>nul') do (
    taskkill /PID %%a /F >nul 2>&1
    echo [OK] Proses API di port 8000 dihentikan.
)

echo.
echo [SELESAI] DEMAKAI API berhasil dihapus dari startup.
echo.
pause
