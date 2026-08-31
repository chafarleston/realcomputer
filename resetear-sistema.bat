@echo off
chcp 65001 >nul
title Reset del Sistema - RealComputer (Chatarra)
REM ============================================================
REM  RESET COMPLETO DEL SISTEMA
REM  Elimina ventas, compras, cajas, pedidos, resúmenes SUNAT,
REM  notas y cola de impresión. Resetea stock a 0 y numeración.
REM  Conserva: productos, categorías, clientes, proveedores,
REM  usuarios, empresas, mesas y el padrón SUNAT.
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
echo  Reset del Sistema - Chatarra (RealComputer)
echo ============================================
echo.
echo PHP:     %PHP%
echo Carpeta: %CD%
echo.
echo Se ELIMINARAN:
echo   - Ventas, compras, cajas, pedidos, resumenes SUNAT
echo   - Notas (NC/ND) y cola de impresion
echo   - Archivos fisicos de comprobantes (CDR) y QR
echo   - Stock de productos se resetea a 0
echo   - Numeracion de series se resetea a 0
echo.
echo Se CONSERVARAN: productos, categorias, clientes, proveedores,
echo usuarios, empresas, mesas y el padron SUNAT.
echo.

choice /C SN /M "Esta accion es irreversible. Desea continuar [S]i / [N]o"
if errorlevel 2 (
    echo.
    echo Operacion cancelada.
    pause
    exit /b 0
)

echo.
echo Ejecutando reset...
"%PHP%" artisan sistema:reset-ventas --force
echo.
echo ============================================
echo  Proceso finalizado. Revise la salida.
echo ============================================
pause