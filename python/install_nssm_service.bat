@echo off
REM ============================================================
REM  install_nssm_service.bat
REM  Install DEMAKAI API sebagai Windows Service via NSSM
REM  API akan aktif otomatis sejak Windows BOOT (bukan hanya login)
REM
REM  PRASYARAT:
REM    Download NSSM dari https://nssm.cc/download
REM    Ekstrak nssm.exe ke: C:\nssm\nssm.exe
REM
REM  CARA PAKAI:
REM    Klik kanan file ini → "Run as administrator"
REM ============================================================

echo =====================================================
echo   DEMAKAI API - Windows Service Setup (NSSM)
echo =====================================================
echo.

REM Cek Administrator
net session >nul 2>&1
if %errorLevel% NEQ 0 (
    echo [ERROR] Harus dijalankan sebagai Administrator!
    pause
    exit /b 1
)

REM Konfigurasi
set NSSM=C:\nssm\nssm.exe
set SERVICE_NAME=DEMAKAI-API
set PYTHON_DIR=D:\magang_bps\backend-demakai\python
set LOG_FILE=%PYTHON_DIR%\api.log
set ERR_FILE=%PYTHON_DIR%\api_err.log

REM Cek NSSM tersedia
if not exist "%NSSM%" (
    echo [ERROR] NSSM tidak ditemukan di: %NSSM%
    echo.
    echo LANGKAH:
    echo   1. Buka: https://nssm.cc/download
    echo   2. Download nssm.zip
    echo   3. Ekstrak nssm.exe ke C:\nssm\nssm.exe
    echo   4. Jalankan script ini lagi
    pause
    exit /b 1
)

REM Cek Python tersedia
where python >nul 2>&1
if %errorLevel% NEQ 0 (
    echo [ERROR] Python tidak ditemukan di PATH!
    echo Pastikan Python terinstall dan ada di sistem PATH.
    pause
    exit /b 1
)

REM Ambil path Python yang digunakan saat ini
for /f "tokens=*" %%i in ('where python') do set PYTHON_EXE=%%i & goto :found_python
:found_python

echo [INFO] Python ditemukan: %PYTHON_EXE%
echo [INFO] Service name    : %SERVICE_NAME%
echo [INFO] Working dir     : %PYTHON_DIR%
echo [INFO] Log file        : %LOG_FILE%
echo.

REM Stop dan hapus service lama jika ada
sc query "%SERVICE_NAME%" >nul 2>&1
if %errorLevel% EQU 0 (
    echo [INFO] Menghapus service lama...
    %NSSM% stop  "%SERVICE_NAME%" >nul 2>&1
    %NSSM% remove "%SERVICE_NAME%" confirm >nul 2>&1
)

REM Install service baru
echo [INFO] Menginstall service...
%NSSM% install "%SERVICE_NAME%" "%PYTHON_EXE%"
%NSSM% set "%SERVICE_NAME%" AppDirectory     "%PYTHON_DIR%"
%NSSM% set "%SERVICE_NAME%" AppParameters    "-m uvicorn api:app --host 127.0.0.1 --port 8000"
%NSSM% set "%SERVICE_NAME%" AppStdout        "%LOG_FILE%"
%NSSM% set "%SERVICE_NAME%" AppStderr        "%ERR_FILE%"
%NSSM% set "%SERVICE_NAME%" AppRotateFiles   1
%NSSM% set "%SERVICE_NAME%" AppRotateBytes   10485760
%NSSM% set "%SERVICE_NAME%" Start            SERVICE_AUTO_START
%NSSM% set "%SERVICE_NAME%" DisplayName      "DEMAKAI Python API"
%NSSM% set "%SERVICE_NAME%" Description      "FastAPI server untuk sistem pencarian KBLI DEMAKAI"

REM Konfigurasi auto-restart jika crash
%NSSM% set "%SERVICE_NAME%" AppRestartDelay  5000
%NSSM% set "%SERVICE_NAME%" AppThrottle      1500

REM Jalankan service sekarang
echo [INFO] Menjalankan service...
%NSSM% start "%SERVICE_NAME%"

if %errorLevel% EQU 0 (
    echo.
    echo =====================================================
    echo   [BERHASIL] DEMAKAI API telah diinstall!
    echo =====================================================
    echo.
    echo   Service  : %SERVICE_NAME%
    echo   Status   : Auto-start setiap Windows BOOT
    echo   URL      : http://127.0.0.1:8000
    echo   Health   : http://127.0.0.1:8000/health
    echo   Log      : %LOG_FILE%
    echo.
    echo   Kelola service:
    echo     Cek status : sc query %SERVICE_NAME%
    echo     Stop       : sc stop %SERVICE_NAME%
    echo     Start      : sc start %SERVICE_NAME%
    echo     Uninstall  : nssm remove %SERVICE_NAME% confirm
    echo.
    timeout /t 3 >nul
    echo Membuka http://127.0.0.1:8000/health untuk verifikasi...
    start "" "http://127.0.0.1:8000/health"
) else (
    echo [ERROR] Gagal menjalankan service. Cek log: %ERR_FILE%
)

echo.
pause
