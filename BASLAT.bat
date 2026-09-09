@echo off
chcp 65001 >nul
title Bilge Asistan
color 0B

echo.
echo  Bilge Asistan baslatiliyor...
echo.

if exist "C:\xampp\xampp-control.exe" start "" "C:\xampp\xampp-control.exe"

tasklist /FI "IMAGENAME eq httpd.exe" 2>nul | find /I "httpd.exe" >nul
if %errorlevel% neq 0 start "" /B "C:\xampp\apache\bin\httpd.exe"

tasklist /FI "IMAGENAME eq mysqld.exe" 2>nul | find /I "mysqld.exe" >nul
if %errorlevel% neq 0 start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini"

set /a sayac=0
:mysql_bekle
set /a sayac+=1
"C:\xampp\mysql\bin\mysql.exe" -u root -e "SELECT 1;" >nul 2>&1
if %errorlevel%==0 goto devam
if %sayac% geq 15 goto hata
timeout /t 2 /nobreak >nul
goto mysql_bekle

:devam
if exist "%~dp0index.php" (
    if not exist "C:\xampp\htdocs\llm_proje" mkdir "C:\xampp\htdocs\llm_proje"
    copy /Y "%~dp0index.php" "C:\xampp\htdocs\llm_proje\" >nul
)

timeout /t 2 /nobreak >nul
start "" "http://localhost/llm_proje/"
timeout /t 1 /nobreak >nul
start "" "http://localhost/phpmyadmin/index.php?route=/sql&db=llm_proje&table=bilgi_bankasi"

if exist "%LOCALAPPDATA%\Programs\Microsoft VS Code\Code.exe" (
    start "" "%LOCALAPPDATA%\Programs\Microsoft VS Code\Code.exe" "C:\xampp\htdocs\llm_proje"
) else (
    code "C:\xampp\htdocs\llm_proje"
)

echo  Hazir.
timeout /t 4
exit

:hata
echo  MySQL baslatilamadi. XAMPP panelinden MySQL'i baslatin.
pause
exit
