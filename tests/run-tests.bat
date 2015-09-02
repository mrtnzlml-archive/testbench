@ECHO OFF
RMDIR /s /q ./temp
SET BIN_TARGET=%~dp0/../vendor/nette/tester/Tester/tester.php
php "%BIN_TARGET%" -c php.ini --coverage-src ./../src/ --coverage ./coverage.html %* ./../tests
