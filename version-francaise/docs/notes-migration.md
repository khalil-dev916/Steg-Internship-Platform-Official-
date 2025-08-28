# Notes de Migration vers la Version Française

Ce document décrit les modifications apportées à la version française par rapport à la version originale en anglais.

## Éléments Supprimés

1. **Éléments spécifiques à Linux**
   - Le script shell `start-dev.sh` a été remplacé par `demarrer-serveur.bat`
   - Les commandes et paramètres spécifiques à Bash ont été remplacés par des commandes Windows

2. **Fichiers de Base de Données**
   - `db.php` → `base.php` (traduit et adapté pour Windows)
   - `steg_interns.db` → `steg_stagiaires.db` (nouveau nom de fichier)

3. **Variables et Commentaires**
   - Tous les commentaires et messages d'erreur ont été traduits en français
   - Les variables restent majoritairement en anglais pour maintenir la compatibilité avec le code

## Nouveaux Éléments

1. **Scripts Windows**
   - `demarrer-serveur.bat` - Script de démarrage pour Windows

2. **Documentation**
   - `docs/installation.md` - Guide détaillé d'installation de PHP sous Windows
   - `LISEZ-MOI.md` - Version française du README avec instructions spécifiques à Windows

3. **Comptes de Test**
   - Les mots de passe ont été adaptés (ex: `intern1` → `stagiaire1`)
   - Le script `utils/comptes-test.js` a été traduit

## Modifications de Structure

La structure des dossiers reste identique à la version anglaise pour faciliter la maintenance:

```
version-francaise/
  ├── assets/
  │   ├── css/
  │   ├── js/
  │   └── images/
  ├── dashboards/
  ├── pages/
  ├── database/
  ├── utils/
  ├── docs/
  ├── index.html
  ├── LISEZ-MOI.md
  └── demarrer-serveur.bat
```

## Notes pour les Développeurs

- La base de code reste compatible avec la version anglaise
- Les modifications sont principalement au niveau de l'interface utilisateur
- Schéma aligné et normalisé: les champs textuels `department` ont été remplacés par `department_id` (FK vers `departments.id`) dans `users` et `interns`. Une migration/backfill est incluse pour renseigner les IDs à partir des anciens noms.
- Les API renvoient toujours le nom du département via jointure pour compatibilité UI existante.
- Pour mettre à jour cette version depuis la version anglaise, suivez les mêmes étapes de traduction
