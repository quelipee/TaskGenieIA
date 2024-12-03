@echo off
:: Muda para o diretório onde o script .bat está localizado (dentro do projeto 'uninter')
cd /d "%~dp0"

:: Executa o comando artisan
php artisan app:uninter

:: Pausa para evitar fechamento automático do terminal
pause
