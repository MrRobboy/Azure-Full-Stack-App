@echo off
REM Script de configuration CORS pour Azure App Service
echo ======================================
echo Configuration CORS pour Azure App Service
echo ======================================

REM Créer un fichier PHP de test pour vérifier les capacités CORS
echo Creating CORS test file...
echo ^<?php > %HOME%\site\wwwroot\cors-test.php
echo header('Content-Type: application/json'); >> %HOME%\site\wwwroot\cors-test.php
echo header('Access-Control-Allow-Origin: *'); >> %HOME%\site\wwwroot\cors-test.php
echo header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); >> %HOME%\site\wwwroot\cors-test.php
echo header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With'); >> %HOME%\site\wwwroot\cors-test.php
echo header('Access-Control-Max-Age: 86400'); >> %HOME%\site\wwwroot\cors-test.php
echo if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { >> %HOME%\site\wwwroot\cors-test.php
echo     http_response_code(204); >> %HOME%\site\wwwroot\cors-test.php
echo     exit; >> %HOME%\site\wwwroot\cors-test.php
echo } >> %HOME%\site\wwwroot\cors-test.php
echo echo json_encode([ >> %HOME%\site\wwwroot\cors-test.php
echo     'success' =^> true, >> %HOME%\site\wwwroot\cors-test.php
echo     'message' =^> 'CORS configuration is working', >> %HOME%\site\wwwroot\cors-test.php
echo     'method' =^> $_SERVER['REQUEST_METHOD'], >> %HOME%\site\wwwroot\cors-test.php
echo     'headers' =^> getallheaders(), >> %HOME%\site\wwwroot\cors-test.php
echo     'timestamp' =^> date('Y-m-d H:i:s') >> %HOME%\site\wwwroot\cors-test.php
echo ]); >> %HOME%\site\wwwroot\cors-test.php
echo ^?^> >> %HOME%\site\wwwroot\cors-test.php

REM Copier les fichiers de configuration
echo Copying configuration files...
if exist %HOME%\site\wwwroot\nginx.conf (
    echo nginx.conf already exists
) else (
    copy nginx.conf %HOME%\site\wwwroot\nginx.conf
    echo nginx.conf copied
)

REM Créer un fichier .htaccess dans le dossier wwwroot
echo Creating .htaccess file...
echo # Enable CORS > %HOME%\site\wwwroot\.htaccess
echo ^<IfModule mod_headers.c^> >> %HOME%\site\wwwroot\.htaccess
echo     Header set Access-Control-Allow-Origin "*" >> %HOME%\site\wwwroot\.htaccess
echo     Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" >> %HOME%\site\wwwroot\.htaccess
echo     Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" >> %HOME%\site\wwwroot\.htaccess
echo     Header set Access-Control-Max-Age "86400" >> %HOME%\site\wwwroot\.htaccess
echo ^</IfModule^> >> %HOME%\site\wwwroot\.htaccess
echo. >> %HOME%\site\wwwroot\.htaccess
echo # Handle OPTIONS method >> %HOME%\site\wwwroot\.htaccess
echo RewriteEngine On >> %HOME%\site\wwwroot\.htaccess
echo RewriteCond %%{REQUEST_METHOD} OPTIONS >> %HOME%\site\wwwroot\.htaccess
echo RewriteRule ^(.*)$ $1 [R=200,L] >> %HOME%\site\wwwroot\.htaccess

echo ======================================
echo Configuration CORS terminée
echo Testez avec: https://YOUR-SITE-NAME.azurewebsites.net/cors-test.php
echo ====================================== 