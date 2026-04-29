@echo off
REM Update Task Scheduler dengan script yang sudah diperbaiki
REM Jalankan sebagai Administrator

net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo [ERROR] Harus dijalankan sebagai Administrator!
    pause
    exit /b 1
)

schtasks /delete /tn "DEMAKAI-Python-API" /f
schtasks /create /tn "DEMAKAI-Python-API" /tr "\"D:\magang_bps\backend-demakai\python\start_api_silent.bat\"" /sc ONLOGON /rl HIGHEST /f

if %errorLevel% EQU 0 (
    echo.
    echo [OK] Task Scheduler berhasil diperbarui!
    echo      API akan otomatis aktif setiap kali login Windows.
    echo.
    echo Status: http://127.0.0.1:8000/health
) else (
    echo [ERROR] Gagal mendaftarkan task.
)
pause
