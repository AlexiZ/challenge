# Challenge Vélo & Marche

Application Symfony de gestion d'un défi de mobilité douce (vélo / marche) entre villes et équipes : inscription des participants, suivi des trajets, photos bonus, QR codes, classements, et back-office pour les admins de ville et le super-admin.

## Stack technique

- PHP 8.3 / Symfony 7.4
- MySQL 8.4 (via Doctrine ORM + Migrations)
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

# Construire les images et démarrer la stack (PHP, MySQL, Adminer, Mailpit)
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
```

## Déploiement

La prod est sur un hébergement mutualisé OVH (offre Perso) : MySQL, accès SFTP seulement, pas de SSH.

Chaque push sur `main` lance le workflow GitHub `.github/workflows/deploy.yml` : tests, puis `bin/deploy`.
Il peut aussi être relancé à la main depuis l'onglet Actions. `make deploy` fait la même chose depuis le
poste local, dans le conteneur PHP.

`bin/deploy` passe par des scripts PHP temporaires au nom aléatoire, appelés en HTTP puis supprimés :

1. un premier script indique le format d'archive que le PHP de la prod sait extraire (zip, sinon tar.gz) ;
2. le projet est construit, empaqueté dans une seule archive et envoyé par SFTP hors de `public/` ;
3. un second script extrait l'archive, supprime les fichiers qui ne font plus partie de la release
   (sauf `var/` et `public/uploads/`), vide le cache de prod, lance les migrations, puis efface
   l'archive et lui-même. Il refuse de s'exécuter s'il a plus de 15 minutes.

Secrets GitHub (Settings > Secrets and variables > Actions) :

- `DEPLOY_ENV` : contenu complet de `deploy.env` ;
- `ENV_PROD_LOCAL` : contenu complet de `.env.prod.local`.

Fichiers locaux à créer pour `make deploy` (non versionnés) :

- `deploy.env` :
  ```
  SFTP_HOST=ftp.cluster130.hosting.ovh.net
  SFTP_USER=...
  SFTP_PASSWORD=...
  REMOTE_PATH=www/challenge   # dossier dédié au projet, relatif à la racine SFTP
  SITE_URL=https://...        # sans slash final
  ```
- `.env.prod.local` : variables de prod (`APP_SECRET`, `DATABASE_URL` vers la base MySQL OVH
  avec `serverVersion=8.4.0&charset=utf8mb4`, `MAILER_DSN`...), figées dans `.env.local.php` au build.

Côté OVH : dans « Multisite », faire pointer le domaine vers `www/challenge/public`. La version de PHP
est fixée par `public/.ovhconfig`.

Attention : l'extraction n'est pas atomique (quelques secondes pendant lesquelles ancienne et nouvelle
version se mélangent), et tout fichier de `REMOTE_PATH` absent de la release est supprimé (sauf `var/`
et `public/uploads/`) : ce dossier doit être réservé au projet.

`make medias` télécharge les uploads de prod, `make push-uploads` envoie les uploads locaux.

## Sécurité

Les éléments suivants ne sont jamais versionnés (voir `.gitignore`) et doivent rester locaux :

- `.env.local`, `.env.*.local` — secrets et configuration spécifiques à l'environnement
- `var/`, `vendor/`, `node_modules/` — fichiers générés / dépendances
- `public/uploads/` — fichiers uploadés par les utilisateurs (avatars, photos bonus)

Ne committez jamais de secrets réels (mots de passe, clés API, `APP_SECRET`) dans `.env` ou dans le code : seules des valeurs par défaut / placeholders doivent y figurer.

## Licence

Ce projet est distribué sous licence [MIT](LICENSE).
