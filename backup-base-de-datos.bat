@echo off
chcp 65001 >nul
title Backup de Base de Datos - RealComputer (Chatarra)
REM ============================================================
REM  BACKUP MANUAL DE LA BASE DE DATOS
REM  Genera un .sql con mysqldump de la BD realcomputer
REM  y lo guarda en storage\app\backup\ con fecha y hora.
REM ============================================================

cd /d "%~dp0"

REM --- Localizar PHP: primero en PATH, luego en Laragon ---
set "PHP="
for /f "delims=" %%i in ('where php 2^>nul') do if not defined PHP set "PHP=%%i"

if not defined PHP (
    for /d %%d in ("C:\laragon\bin\php\php-*") do (
        if not defined PHP if exist "%%d\php.exe" set "PHP=%%d\php.exe"
    )
)

if not defined PHP (
    echo [ERROR] No se encontro PHP. Instale PHP o Laragon.
    pause
    exit /b 1
)

echo ============================================
echo  Backup de Base de Datos - Chatarra
echo ============================================
echo.
echo PHP:     %PHP%
echo Carpeta: %CD%
echo.

"%PHP%" artisan sistema:backup
echo.
echo ============================================
echo  Proceso finalizado.
echo ============================================
pause