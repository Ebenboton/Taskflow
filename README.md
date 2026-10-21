# TaskFlow

**Application web de gestion de tâches avec PHP et MySQL**

![PHP](https://img.shields.io/badge/PHP-8%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8%2B-4479A1?logo=mysql&logoColor=white)
![Licence](https://img.shields.io/badge/Licence-MIT-green)
![Statut](https://img.shields.io/badge/Statut-Terminé-success)

---

## Présentation du projet

TaskFlow est une application web de gestion de tâches développée en **PHP orienté objet (POO)** et **MySQL**, dans le cadre de la réalisation d'un projet personnel.

Elle permet aux utilisateurs d'organiser leurs tâches, de gérer leurs catégories, de suivre leur progression et d'exporter leurs données.

Ce projet a pour objectif de démontrer la maîtrise du développement web, notamment en matière d'authentification, de sécurité, de gestion des données et de conception d'une interface responsive.

## Fonctionnalités

### 1. Authentification et gestion des comptes

- Inscription avec validation du nom d'utilisateur, de l'adresse e-mail et du mot de passe.
- Connexion sécurisée avec `password_hash()` et `password_verify()`.
- Déconnexion sécurisée par requête POST avec protection CSRF.
- Gestion des sessions utilisateur.

### 2. Gestion des catégories

- Création automatique de six catégories lors de l'inscription.
- Opérations CRUD : création, consultation, modification et suppression.
- Personnalisation de la couleur de chaque catégorie.
- Affichage du nombre de tâches associées à chaque catégorie.

### 3. Gestion des tâches

- Création, consultation, modification et suppression des tâches.
- Association facultative d'une tâche à une catégorie.
- Gestion du titre, de la description, de la date d'échéance, de la priorité et du statut.
- Trois statuts : à faire, en cours et terminée.
- Trois niveaux de priorité : basse, moyenne et haute.
- Modification rapide du statut à l'aide d'une case à cocher.
- Détection automatique des tâches en retard.

### 4. Recherche et filtres

- Filtrage des tâches par catégorie, statut et priorité.
- Recherche par titre ou description.
- Export des résultats selon les filtres appliqués.

### 5. Tableau de bord

- Affichage des statistiques en temps réel.
- Indicateur circulaire de progression.
- Aperçu des cinq prochaines tâches.
- Répartition des tâches par catégorie.
- Accès rapide aux principales fonctionnalités.

### 6. Exportation des données

- Export des tâches au format CSV.
- Export global ou limité aux résultats filtrés.
- Encodage UTF-8 avec BOM pour améliorer la compatibilité avec Excel.
- Utilisation du point-virgule comme séparateur pour faciliter l'ouverture dans Excel et Google Sheets.

### 7. Interface et expérience utilisateur

- Interface moderne et épurée.
- Mise en page responsive pour les ordinateurs, les tablettes et les smartphones.
- Icônes SVG intégrées directement au code, sans dépendance externe pour leur affichage.
- Navigation et formulaires adaptés à la gestion quotidienne des tâches.

## Sécurité

La sécurité constitue un aspect important de TaskFlow. Plusieurs mécanismes sont prévus pour protéger les comptes et les données des utilisateurs.

| Protection | Implémentation |
|---|---|
| Injections SQL | Requêtes préparées avec PDO |
| Mots de passe | `password_hash()` et `password_verify()` |
| Failles XSS | Échappement des sorties avec `htmlspecialchars()` |
| Attaques CSRF | Jetons de validation sur les formulaires POST |
| Fixation de session | Régénération de l'identifiant après connexion |
| Tentatives de connexion abusives | Limitation à cinq tentatives sur quinze minutes, selon l'IP et l'e-mail |
| Cookies de session | Attributs `HttpOnly`, `SameSite=Lax` et `Secure` en HTTPS |
| Énumération de comptes | Messages d'erreur génériques |
| Expiration de session | Destruction de la session après deux heures d'inactivité |
| Régénération périodique de session | Renouvellement de l'identifiant toutes les trente minutes |

**Remarque :** ces protections doivent être vérifiées dans le code et testées dans l'environnement de déploiement avant de considérer l'application comme entièrement sécurisée.

## Technologies utilisées

| Composant | Technologie |
|---|---|
| Backend | PHP 8+ orienté objet |
| Base de données | MySQL |
| Interface | HTML5 et CSS3 |
| Interactivité | JavaScript natif |
| Accès aux données | PDO |
| Serveur de développement | XAMPP |
| Gestion de versions | Git et GitHub |

## Architecture du projet

```text
taskflow/
├── config/
│   ├── database.php
│   └── session.php
├── public/
│   ├── index.php
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── tasks.php
│   ├── categories.php
│   ├── export.php
│   ├── partials/
│   │   ├── header.php
│   │   └── icons.php
│   └── assets/
│       ├── css/
│       │   ├── base.css
│       │   ├── components.css
│       │   ├── header.css
│       │   ├── auth.css
│       │   ├── dashboard.css
│       │   ├── tasks.css
│       │   └── categories.css
│       └── js/
├── src/
│   ├── Database.php
│   ├── Auth.php
│   ├── CSRF.php
│   ├── LoginThrottle.php
│   ├── Category.php
│   └── Task.php
├── sql/
│   └── schema.sql
├── docs/
│   └── (captures d'écran)
├── .gitignore
├── LICENSE
└── README.md
```

## Installation

### Prérequis

- PHP 8 ou une version ultérieure.
- MySQL.
- XAMPP ou un environnement de développement PHP équivalent.
- Git, si le projet est récupéré depuis GitHub.

### Étapes d'installation

**1. Récupérer le projet**

Clone le dépôt GitHub ou place le dossier `taskflow` dans le répertoire de tes projets locaux.

**2. Configurer la base de données**

Crée une base de données MySQL, puis importe le fichier `sql/schema.sql` depuis phpMyAdmin ou la ligne de commande.

**3. Configurer la connexion**

Modifie le fichier `config/database.php` pour renseigner les paramètres de connexion à ta base de données.

**4. Démarrer l'application**

Démarre Apache et MySQL dans XAMPP, puis accède au point d'entrée public de l'application depuis ton navigateur.

Adapte l'URL au nom du dossier et à la configuration de ton serveur local.
