@echo off
setlocal

SET PORT=8000
SET HOST=localhost
SET PROJECT_ROOT=%CD%

echo [1/6] Vérification de la structure du projet...
IF NOT EXIST "assets" mkdir assets
IF NOT EXIST "assets\css" mkdir assets\css
IF NOT EXIST "assets\js" mkdir assets\js
IF NOT EXIST "assets\images" mkdir assets\images
IF NOT EXIST "dashboards" mkdir dashboards
IF NOT EXIST "pages" mkdir pages
IF NOT EXIST "database" mkdir database
IF NOT EXIST "utils" mkdir utils

echo [2/6] Suppression de l'ancienne base de données SQLite (si elle existe)...
IF EXIST "database\steg_stagiaires.db" del /f "database\steg_stagiaires.db"
IF NOT EXIST "database" mkdir database

echo [3/6] Vérification de la disponibilité de PHP...
where php >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
  echo ERREUR: PHP n'est pas trouvé dans le PATH. Veuillez installer PHP et l'ajouter à votre PATH.
  echo Vous pouvez télécharger PHP depuis https://windows.php.net/download/
  echo Après l'installation, ajoutez le dossier de PHP à votre PATH système.
  pause
  exit /b 1
)

echo [4/6] Vérification des chemins de fichiers et de la structure...
IF NOT EXIST "index.html" (
  echo AVERTISSEMENT: index.html n'est pas trouvé. L'application peut ne pas fonctionner correctement.
)

echo [5/6] Initialisation de la base de données via base.php...
php -r "require 'database/base.php';" || (
  echo L'initialisation de la base de données a échoué
  pause
  exit /b 1
)

IF NOT EXIST "database\steg_stagiaires.db" (
  echo ERREUR: Le fichier de base de données n'a pas été créé.
  pause
  exit /b 1
)

echo Base de données créée avec succès. Comptes de test (email / mot de passe) :
echo   Admin:    admin@steg.tn            / admin123
echo   Directeur: directeur@steg.tn       / directeur123
echo   Superviseurs:
echo     - samira.bensalah@steg.tn        / superviseur1
echo     - mohamed.trabelsi@steg.tn       / superviseur2
echo     - fatma.khalil@steg.tn           / superviseur3
echo   Stagiaires:
echo     - firas.welhazi@email.com        / stagiaire1
echo     - samira.khedher@email.com       / stagiaire2
echo     - ahmed.bensalah@email.com       / stagiaire3
echo.
echo La connexion utilise uniquement l'EMAIL (pas de nom d'utilisateur). La réinitialisation du mot de passe est désactivée (contactez l'administrateur).

echo [6/6] Démarrage du serveur de développement PHP sur http://%HOST%:%PORT% ... (Ctrl+C pour arrêter)
echo Vous pouvez accéder à l'application à: http://%HOST%:%PORT%
echo.

REM Création du fichier d'informations des comptes de test pour la page de connexion
echo // Informations des comptes de test pour la page de connexion > utils\comptes-test.js
echo const comptesTest = { >> utils\comptes-test.js
echo   admin: { >> utils\comptes-test.js
echo     email: "admin@steg.tn", >> utils\comptes-test.js
echo     password: "admin123", >> utils\comptes-test.js
echo     description: "Accès complet au système" >> utils\comptes-test.js
echo   }, >> utils\comptes-test.js
echo   directeur: { >> utils\comptes-test.js
echo     email: "directeur@steg.tn", >> utils\comptes-test.js
echo     password: "directeur123", >> utils\comptes-test.js
echo     description: "Supervision des départements" >> utils\comptes-test.js
echo   }, >> utils\comptes-test.js
echo   superviseur: { >> utils\comptes-test.js
echo     email: "samira.bensalah@steg.tn", >> utils\comptes-test.js
echo     password: "superviseur1", >> utils\comptes-test.js
echo     description: "Gestion des stagiaires" >> utils\comptes-test.js
echo   }, >> utils\comptes-test.js
echo   stagiaire: { >> utils\comptes-test.js
echo     email: "firas.welhazi@email.com", >> utils\comptes-test.js
echo     password: "stagiaire1", >> utils\comptes-test.js
echo     description: "Compte stagiaire" >> utils\comptes-test.js
echo   } >> utils\comptes-test.js
echo }; >> utils\comptes-test.js

REM Démarrage du serveur PHP
php -S %HOST%:%PORT%
echo Le serveur s'est arrêté. Appuyez sur une touche pour quitter.
pause
