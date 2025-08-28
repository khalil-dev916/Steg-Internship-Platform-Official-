# Guide d'Installation et Configuration - Système de Gestion des Stagiaires STEG

## Installation de PHP sous Windows

1. **Télécharger PHP**
   - Visitez [windows.php.net](https://windows.php.net/download/)
   - Téléchargez la dernière version stable de PHP (par exemple PHP 8.2.x) en version "Thread Safe" (x64 ou x86 selon votre système)
   - Cliquez sur le lien "Zip" pour télécharger l'archive

2. **Installation**
   - Créez un dossier `C:\php` sur votre ordinateur
   - Extrayez le contenu de l'archive ZIP dans ce dossier
   - Copiez le fichier `php.ini-development` et renommez la copie en `php.ini`

3. **Configuration de PHP**
   - Ouvrez le fichier `php.ini` avec un éditeur de texte (comme Notepad++)
   - Recherchez et décommentez (supprimez le point-virgule au début) les lignes suivantes:
     ```
     extension=pdo_sqlite
     extension=sqlite3
     ```
   - Enregistrez le fichier

4. **Ajouter PHP au PATH**
   - Cliquez-droit sur "Ce PC" ou "Ordinateur" et sélectionnez "Propriétés"
   - Cliquez sur "Paramètres système avancés"
   - Cliquez sur le bouton "Variables d'environnement"
   - Dans la section "Variables système", trouvez la variable "Path" et double-cliquez dessus
   - Cliquez sur "Nouveau" et ajoutez `C:\php`
   - Cliquez sur "OK" pour fermer toutes les fenêtres

5. **Vérifier l'installation**
   - Ouvrez une invite de commande (cmd.exe)
   - Tapez `php -v` et appuyez sur Entrée
   - Si PHP est correctement installé, vous verrez la version affichée

## Démarrer l'Application

1. Double-cliquez sur le fichier `demarrer-serveur.bat` dans le dossier racine du projet

2. Un terminal s'ouvrira et vous verrez les messages suivants:
   - Vérification de la structure du projet
   - Initialisation de la base de données
   - Démarrage du serveur PHP

3. Attendez jusqu'à voir "Démarrage du serveur de développement PHP sur http://localhost:8000"

4. Ouvrez votre navigateur et accédez à [http://localhost:8000](http://localhost:8000)

## Problèmes courants

### PHP non trouvé
- **Erreur**: "PHP n'est pas reconnu en tant que commande interne ou externe"
- **Solution**: Vérifiez que PHP est correctement ajouté au PATH

### Erreur de base de données
- **Erreur**: "Erreur : could not find driver"
- **Solution**: Vérifiez que les extensions SQLite sont activées dans le fichier php.ini

### Port déjà utilisé
- **Erreur**: "Le port 8000 est déjà utilisé"
- **Solution**: Modifiez la variable PORT dans le fichier demarrer-serveur.bat

### Page blanche ou erreurs JavaScript
- **Solution**: Appuyez sur F12 dans votre navigateur pour voir les erreurs dans la console
