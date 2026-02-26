# Storylia

[Voir le dépôt](https://github.com/Gaedolen/Storylia.git)

[![Symfony](https://img.shields.io/badge/Symfony-5.10.4-blue)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2.12-brightgreen)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-lightgrey)](LICENSE)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15-blue)](https://www.postgresql.org/)
[![MongoDB](https://img.shields.io/badge/MongoDB-latest-brightgreen)](https://www.mongodb.com/)
[![Heroku](https://img.shields.io/badge/Heroku-deploy-purple)](https://www.heroku.com/)

Storylia est un site de gestion de bibliothèque, de partage et de création de clubs de lecture.  
L'objectif est de permettre la création de communautés livresques facilement, même à distance, et d’offrir une expérience centralisée pour gérer sa bibliothèque, participer à des clubs et interagir avec d’autres passionnés de lecture.

---

## Table des matières
- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Installation](#installation)
- [Configuration](#configuration)
- [Exécution en développement](#exécution-en-développement)
- [Tests](#tests)
- [Déploiement](#déploiement)
- [Contributeurs](#contributeurs)
- [Licence](#licence)

---

## Fonctionnalités

### Utilisateur
- Ajouter des livres à sa bibliothèque personnelle ou créer de nouveaux livres.
- Créer, participer et gérer un club de lecture.
- Proposer des livres à lire dans un club.
- Laisser un avis et une note sur les livres lus.
- Mettre à jour ses livres.
- Visualiser sa bibliothèque et son historique de lecture.
- Voter pour le prochain livre à lire dans un club.

### Employé
- Modération des avis, utilisateurs, clubs et livres.

### Administrateur
- Suspendre un club, un utilisateur ou désactiver un livre.
- Modifier des livres et gérer la base de données via API.
- Créer et supprimer des comptes employés.
- Visualiser les statistiques du site.

---

## Technologies
- Backend : PHP 8.2.12, Symfony 5.10.4, Doctrine ORM, Doctrine MongoDB ODM
- Base de données principale : PostgreSQL
- Base NoSQL pour logs : MongoDB
- Frontend : Twig, JavaScript, CSS, HTML
- Mailer : Symfony Mailer
- API externe : Google Books

---

## Installation

### Prérequis
- PHP >= 8.2
- Composer
- PostgreSQL
- MongoDB
- Node.js + npm (si assets à compiler)
- Symfony CLI (optionnel mais recommandé)

### Étapes

1. Cloner le projet  
```bash
git clone <REPO_URL> storylia
cd storylia
```

2. Installer les dépendances PHP
```bash
composer install
```

3. Installer les dépendances frontend
```bash
npm install
```

4. Configurer la BDD PostgreSQL
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Configurer MongoDB pour les logs 
```bash
MONGODB_URL=mongodb://127.0.0.1:27017
MONGODB_DB=storylia_nosql
```

## Configuration

# Exemple de fichier .env :

```env
APP_ENV=dev
APP_SECRET=ChangeMe12345

DATABASE_URL="postgresql://app:password@127.0.0.1:5432/storylia?serverVersion=15&charset=utf8"

MONGODB_URL="mongodb://127.0.0.1:27017"
MONGODB_DB="storylia_nosql"

MAILER_DSN=smtp://user:password@smtp.mailtrap.io:2525

GOOGLE_BOOKS_API=<VOTRE_API_KEY>
```

## Exécution en développement

### Lancer le server Symfony :
```bash
symfony serve
```

### Accéder à l'application :
http://127.0.0.1:8000

## Tests
```bash
php bin/phpunit
```

## Déploiement

1. Créer une app Heroku

2. Ajouter PostgreSQL et MongoDB

3. Définir les variables d'environnement sur Heroku

- APP_ENV=prod
- APP_SECRET=<votre_secret>
- DATABASE_URL
- MONGODB_URL et MONGODB_DB
- MAILER_DSN
- GOOGLE_BOOKS_API

4. Pousser le code sur Heroku
```bash
git push heroku main
```

5. Lancer les migrations
```bash
heroku run php bin/console doctrine:migrations:migrate
```

6. Vider le cache et générer les assets
```bash
heroku run php bin/console cache:clear --env=prod
heroku run php bin/console assets:install
```

## Contributeurs
- Clélia Panini