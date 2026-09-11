# Challenge Vélo & Marche

Application Symfony de gestion d'un défi de mobilité douce (vélo / marche) entre villes et équipes : inscription des participants, suivi des trajets, photos bonus, QR codes, classements, et back-office pour les admins de ville et le super-admin.

## Stack technique

- PHP 8.3 / Symfony 7.4
- PostgreSQL (via Doctrine ORM + Migrations)
- AssetMapper + Bootstrap 5 + Font Awesome 6 (npm)
- Docker Compose, FrankenPHP (Caddy) — HTTPS local automatique

## Démarrage local

### Prérequis

- Docker et Docker Compose

### Installation

```bash
git clone git@github.com:AlexiZ/challenge.git
cd challenge

# Copier la configuration d'environnement et l'adapter à votre machine
cp .env .env.local
# Éditer .env.local : APP_SECRET, DATABASE_URL, MAILER_DSN, etc.
# Ce fichier est ignoré par git et ne doit jamais être commité.

# Construire les images et démarrer la stack (PHP, PostgreSQL, Adminer, Mailpit)
make docker-up
# équivalent à : docker compose up --build -d

# Charger les alias
source .spells

# Installer les dépendances
composer install
npm install

# Appliquer les migrations
sf doctrine:migrations:migrate
# ou : make load-fixtures
```

L'application est accessible sur http://localhost:8080 (HTTP) ou directement
sur https://localhost:8443 (HTTPS) — le certificat est auto-signé (CA interne
de Caddy), le navigateur affichera un avertissement à accepter la première
fois. Adminer sur http://localhost:8081, Mailpit sur http://localhost:8025.

Pour arrêter la stack : `make docker-down`.

### Raccourcis shell

Le fichier `.spells` expose des fonctions pratiques qui exécutent les commandes dans le conteneur PHP :

```bash
source .spells

php bin/console about
composer require ...
sf make:controller ...
deployer ssh prod ...
```

## Sécurité

Les éléments suivants ne sont jamais versionnés (voir `.gitignore`) et doivent rester locaux :

- `.env.local`, `.env.*.local` — secrets et configuration spécifiques à l'environnement
- `var/`, `vendor/`, `node_modules/` — fichiers générés / dépendances
- `public/uploads/` — fichiers uploadés par les utilisateurs (avatars, photos bonus)

Ne committez jamais de secrets réels (mots de passe, clés API, `APP_SECRET`) dans `.env` ou dans le code : seules des valeurs par défaut / placeholders doivent y figurer.

## Licence

Ce projet est distribué sous licence [MIT](LICENSE).
