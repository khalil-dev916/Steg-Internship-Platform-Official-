# Système de Gestion des Stagiaires pour STEG

Ce projet est un système de gestion des stagiaires pour la Société Tunisienne de l'Électricité et du Gaz (STEG), permettant de suivre les stages, les évaluations et la documentation des stagiaires.

## Configuration Requise

- Windows 7 ou plus récent
- PHP 7.4 ou plus récent (téléchargeable sur [windows.php.net](https://windows.php.net/download/))
- Navigateur web moderne (Chrome, Firefox, Edge)

## Comment démarrer l'application

1. **Installation de PHP** (si ce n'est pas déjà fait) :
   - Téléchargez PHP depuis [windows.php.net](https://windows.php.net/download/)
   - Choisissez la version "Thread Safe" pour Windows
   - Extrayez le contenu du ZIP dans un dossier (par exemple `C:\php`)
   - Ajoutez ce dossier à votre variable d'environnement PATH
     - Cliquez-droit sur "Ce PC" -> Propriétés -> Paramètres système avancés
     - Cliquez sur "Variables d'environnement"
     - Dans "Variables système", trouvez et sélectionnez "Path"
     - Cliquez sur "Modifier" puis "Nouveau"
     - Ajoutez le chemin vers votre dossier PHP (ex: `C:\php`)
     - Cliquez sur "OK" pour fermer toutes les fenêtres

2. **Activation des extensions PHP nécessaires** :
   - Naviguez vers votre dossier PHP
   - Créez une copie du fichier `php.ini-development` et renommez-la en `php.ini`
   - Ouvrez `php.ini` dans un éditeur de texte
   - Décommentez ces lignes (supprimez le ; au début) :
     ```
     extension=pdo_sqlite
     extension=sqlite3
     ```

3. **Lancement de l'application** :
   - Double-cliquez sur le fichier `demarrer-serveur.bat`
   - Un terminal Windows s'ouvrira et démarrera le serveur
   - Attendez jusqu'à ce que vous voyiez "Démarrage du serveur PHP sur http://localhost:8000"
   - Ouvrez votre navigateur et accédez à `http://localhost:8000`

4. **Connexion à l'application** :
   - Utilisez l'un des comptes de test listés dans la fenêtre du terminal
   - Format : adresse e-mail / mot de passe

## Rôles des utilisateurs

1. **Administrateur** (admin@steg.tn / admin123)
   - Gestion complète des utilisateurs, départements et stagiaires
   - Accès à toutes les fonctionnalités

2. **Directeur** (directeur@steg.tn / directeur123)
   - Vue d'ensemble des départements et des statistiques
   - Approbation des demandes importantes

3. **Superviseur** (samira.bensalah@steg.tn / superviseur1)
   - Suivi des stagiaires assignés
   - Évaluation des performances
   - Gestion des attestations de stage

4. **Stagiaire** (firas.welhazi@email.com / stagiaire1)
   - Soumission de rapports
   - Consultation des évaluations
   - Accès aux documents

## Dépannage

- **PHP non reconnu** : Assurez-vous que PHP est correctement installé et ajouté au PATH
- **Erreurs de base de données** : Vérifiez que les extensions SQLite sont activées dans php.ini
- **Page blanche** : Vérifiez les erreurs dans la console du navigateur (F12)
- **Erreur de port** : Si le port 8000 est déjà utilisé, modifiez la variable PORT dans demarrer-serveur.bat

Pour toute assistance supplémentaire, contactez le service informatique.
