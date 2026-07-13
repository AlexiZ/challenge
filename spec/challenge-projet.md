# Challenge Tout à Vélo & Marche — Descriptif du projet

## Contexte

Le **Challenge Tout à Vélo & Marche** est un événement annuel organisé par des associations locales bretonnes (une par ville participante) pour encourager les déplacements utilitaires à vélo et à pied. Ce document décrit la refonte complète de la plateforme web existante, développée sous Symfony, intégrant pour la première fois la **marche à pied** en plus du vélo.

La plateforme est multi-villes et multi-éditions. Chaque édition annuelle regroupe un ensemble de villes participantes. L'URL racine (`/`) est la vitrine globale ; les pages propres à une ville sont accessibles sous le préfixe `/{citySlug}/`.

---

## Stack technique cible

- **Framework** : Symfony 7.x (PHP 8.3+)
- **Base de données** : PostgreSQL
- **ORM** : Doctrine
- **Frontend** : Twig + CSS vanilla — thème "Route mouillée" : fond crème `#F7F5F2`, teal `#1A9B96`, orange `#F5821F`
- **Authentification** : Symfony Security (voters, roles)
- **Upload fichiers** : VichUploaderBundle (photos de profil, logos partenaires, photos bonus)
- **Emails** : Symfony Mailer + transport Brevo (anciennement Sendinblue) via `symfony/brevo-mailer`
- **Multi-villes** : chaque ville est identifiée par son `slug` utilisé comme préfixe de route `/{citySlug}/` ; un `CitySlugResolver` injecte la `City` active depuis le paramètre de route

---

## Rôles utilisateurs

| Rôle | Description |
|---|---|
| `ROLE_USER` | Participant inscrit à une ville |
| `ROLE_ADMIN_VILLE` | Administrateur d'une ville (back-office limité à sa ville) |
| `ROLE_SUPER_ADMIN` | Accès total : gestion des villes, des éditions, des admins |

---

## Entités principales (modèle de données)

### `City`
Représente une ville participante.
- `id`, `name`, `slug` — slug unique, préfixe d'URL (`/plabennec/...`)
- `organizationName` — nom de l'association organisatrice
- `organizationDescription` — texte court affiché sur l'accueil de la ville
- `organizationLogo` — fichier image
- `contactEmail`
- `socialLink1Type` (enum : facebook, instagram, twitter, website), `socialLink1Url`
- `socialLink2Type`, `socialLink2Url`
- Relations : `OneToMany` → `CityEdition`, `OneToMany` → `Partner`, `OneToMany` → `Team`

### `Edition`
Représente une édition annuelle du challenge, gérée par le `ROLE_SUPER_ADMIN`.
- `id`, `name`, `year`
- `startDate`, `endDate` — dates officielles communes à toutes les villes participantes
- `warmupStartDate`, `warmupEndDate` — période de chauffe optionnelle (km ne comptent pas dans les points)
- Relations : `OneToMany` → `CityEdition`

### `CityEdition`
Participation d'une ville à une édition. Entité pivot portant toute la configuration propre à une ville pour une édition donnée.
- `id`
- `city` — `ManyToOne` → `City`
- `edition` — `ManyToOne` → `Edition`
- Contrainte d'unicité : `(city, edition)`
- `targetParticipants` (int)
- `targetDistanceKm` (int)
- `pointsPerDay` (float, défaut : 1.0)
- `pointsPerKmBike` (float, défaut : 0.1)
- `pointsPerKmWalk` (float, défaut : 0.2)
- `suspiciousDistanceBike` (float, défaut : 200.0) — seuil anomalie vélo
- `suspiciousDistanceWalk` (float, défaut : 50.0) — seuil anomalie marche
- `registrationsOpen` (bool), `tripsEntryOpen` (bool), `photoChallengesEnabled` (bool)
- `rankingsPublic` (bool), `profilesPublic` (bool)
- `organizerMessage` (text, nullable)
- Relations : `ManyToMany` → `User` (participants inscrits), `OneToMany` → `Trip`, `OneToMany` → `BonusPhoto`, `OneToMany` → `CounterStep`, `OneToMany` → `BonusPhotoConfig`

À la création d'une `CityEdition` (par le `ROLE_SUPER_ADMIN`), un listener Doctrine crée automatiquement les 8 entrées `BonusPhotoConfig` correspondantes avec leurs valeurs par défaut.

### `BonusPhotoConfig`
Points attribués par type de défi photo pour une `CityEdition`. Configurables par le `ROLE_ADMIN_VILLE` en P16.
- `id`
- `cityEdition` — `ManyToOne` → `CityEdition`
- `challenge` (enum : le_parrain, panier_garni, classe_a_plach, pour_le_plaisir, famille_nombreuse, tshirt_mouille, esprit_equipe, panoramacyclette)
- `points` (float)
- Contrainte d'unicité : `(cityEdition, challenge)`

Valeurs par défaut à la création :

| Défi | Points par défaut |
|---|---|
| Le Parrain | 5 |
| Panier garni | 3 |
| Classe à plach | 4 |
| Pour le plaisir | 3 |
| Famille nombreuse | 5 |
| T-shirt mouillé | 3 |
| Esprit d'équipe | 4 |
| Panoramacyclette | 4 |

### `CounterStep`
Étapes du compteur collectif (Tour de Bretagne, Tour de France…), propres à une `CityEdition`.
- `id`, `name`, `distanceKm` (int), `position` (int, pour l'ordre)
- `cityEdition` — `ManyToOne` → `CityEdition`

### `User`
Participant ou administrateur.
- `id`, `email`, `password` (hashé), `roles` (array)
- `firstName`, `lastName`
- `username` — pseudo unique, utilisé dans les URLs de profil (`/{citySlug}/participants/{username}`) ; généré automatiquement à l'inscription (`prenom.nom`), puis modifiable
- `cyclistProfile` (enum : novice, occasional, regular)
- `bikeType` (enum : classic, vtc, vae, cargo, other)
- `perceivedBenefit` (enum, nullable : health, ecology, economy, sport, other)
- `mainBarrier` (enum, nullable : distance, weather, hills, safety, equipment, other)
- `gender` (enum), `age` (int)
- `receiveEmails` (bool), `publicProfile` (bool)
- `avatar` — fichier image (VichUploader)
- `personalToken` — UUID v4, accès mobile sans mot de passe ; régénérable depuis P12
- `city` — `ManyToOne` → `City` (ville de rattachement principale)
- Relations : `ManyToMany` → `Team`, `OneToMany` → `Trip`, `OneToMany` → `BonusPhoto`

### `Team`
Équipe au sein d'une ville.
- `id`, `name`, `slug` — unique par ville, utilisé dans l'URL `/{citySlug}/equipes/{slug}`
- `description`, `isPublic` (bool)
- `avatar` — fichier image (VichUploader)
- `city` — `ManyToOne` → `City`
- `createdBy` — `ManyToOne` → `User`
- Relations : `ManyToMany` → `User`

### `Trip`
Trajet déclaré par un participant.
- `id`
- `user` — `ManyToOne` → `User`
- `cityEdition` — `ManyToOne` → `CityEdition`
- `mode` (enum : bike, walk)
- `distanceKm` (float) — minimum 0.5, pas de 0.5 km (UI mobile)
- `tripDate` (date) — date du trajet (pas de la saisie)
- `createdAt` (datetime) — date/heure de saisie
- `pointsGenerated` (float) — calculé et persisté à la sauvegarde
- Propriété calculée (non persistée) : `isSuspicious`

### `BonusPhoto`
Photo soumise pour un défi bonus.
- `id`
- `user` — `ManyToOne` → `User`
- `cityEdition` — `ManyToOne` → `CityEdition`
- `challenge` (enum : le_parrain, panier_garni, classe_a_plach, pour_le_plaisir, famille_nombreuse, tshirt_mouille, esprit_equipe, panoramacyclette)
- `photo` — fichier image (VichUploader)
- `comment` (text, nullable)
- `consentToPublish` (bool)
- `status` (enum : pending, approved, rejected)
- `pointsAwarded` (float, nullable) — saisi par l'admin à la validation ; la valeur par défaut proposée est lue depuis `BonusPhotoConfig`
- `submittedAt` (datetime), `reviewedAt` (datetime, nullable)
- `reviewedBy` — `ManyToOne` → `User` (admin)

### `Partner`
Partenaire affiché sur la page Infos pratiques d'une ville.
- `id`, `name`, `websiteUrl` (nullable), `description` (nullable)
- `logo` — fichier image (VichUploader)
- `position` (int) — ordre d'affichage
- `city` — `ManyToOne` → `City`

---

## Calcul des points

```
points_participant = (nb_jours_actifs × CityEdition.pointsPerDay)
                   + (total_km_vélo × CityEdition.pointsPerKmBike)
                   + (total_km_marche × CityEdition.pointsPerKmWalk)
                   + total_BonusPhoto.pointsAwarded (status = approved)
```

**Points équipe** = moyenne des points de tous ses membres (pour ne pas avantager les grandes équipes).

Un **jour actif** = au moins un `Trip` saisi pour cette date dans la période officielle (`Edition.startDate` → `Edition.endDate`, hors warmup).

### Calcul du CO₂ évité

```
co2_evite_kg = (total_km_vélo + total_km_marche) × 0.193
```

Coefficient ADEME (193 g CO₂/km évité vs voiture). Valeur codée en constante dans `Co2Calculator::COEFFICIENT_KG_PER_KM`. Ce service est injecté partout où le CO₂ est affiché (P00, P01, P02, P05, P13, exports CSV).

---

## Classement inter-villes

Le classement inter-villes est basé sur le **score collectif** de chaque ville : somme des points de tous les participants de la ville pour l'édition concernée. Ce score est mis en cache (TTL 5 min). Il est affiché sur l'accueil global P00 et en sidebar sur P01, P02, P03.

---

## Accès mobile sans mot de passe (lien QR)

La route `/mobile/{token}` est globale (sans préfixe ville). Elle affiche la saisie rapide sans session. Le token est régénérable depuis P12 ; la régénération invalide l'ancien lien QR.

---

## Pages — descriptif fonctionnel

---

### P00 — Accueil global (`/`)

**Accès** : public

**Contenu** :
- Navigation globale : logo plateforme, lien Connexion
- Hero : présentation du challenge (titre, description, chiffres clés de l'édition en cours)
- **Classement inter-villes** : tableau ou liste des villes classées par score collectif (nom, participants, km, points) — édition en cours
- **Grille des villes participantes** : carte par ville (nom, logo association, nb participants, lien "Rejoindre le challenge") ; villes sans édition active affichées en grisé
- Stats globales de la plateforme : nb villes actives, nb participants total, km totaux, CO₂ évité

**Données nécessaires** : liste des `City` avec leur `CityEdition` active, scores collectifs (cache)

---

### P01 — Accueil ville (`/{citySlug}/`)

**Accès** : public

**Contenu** :
- Navigation : logo + liens + boutons Connexion / Inscription
- Hero : titre, description du challenge, CTA inscription + voir les règles, note "Gratuit · VAE acceptés · Ouvert à tous"
- Carte live : stats de la ville (participants, distance vélo + marche, jours actifs, CO₂ évité) — cache TTL 1 min
- Encart association organisatrice : logo, nom, description courte, liens réseaux sociaux (depuis `City`)
- Section "Comment ça marche ?" : 4 étapes visuelles
- Classement inter-villes (toutes villes de l'édition en cours) + liste des derniers participants actifs (2 colonnes)
- Bande CTA inscription avec date de fin de challenge
- Footer

---

### P02 — Dashboard participant (`/{citySlug}/mon-challenge`)

**Accès** : `ROLE_USER` connecté, appartenant à cette ville

**Contenu** :
- Barre de progression : jours actifs / jours écoulés / durée totale
- Message des organisateurs (`CityEdition.organizerMessage`)
- Bloc stats "langage humain" : jours, km, points
- Formulaire de saisie rapide inline : toggle vélo/marche, km, date, bouton Valider
- Liste des 5 derniers trajets avec suppression ; lien "Voir plus"
- Carte bonus photo (CTA vers P11)
- Historique des éditions passées : Édition, Jours, Km, Points, CO₂ évité
- **Sidebar** : compteur collectif (jours, km avec tooltip vélo/marche, CO₂, étapes `CounterStep`), classement inter-villes compact, QR code + bouton "Régénérer le lien"

**Actions** : `POST /{citySlug}/trips`, `DELETE /{citySlug}/trips/{id}`

---

### P03 — Classements (`/{citySlug}/classements`)

**Accès** : public si `CityEdition.rankingsPublic = true`, sinon `ROLE_USER`

**Contenu** :
- Onglets : "Par équipe" / "Individuel"
- Filtre mode : Tous / Vélo / Marche — recalcul côté serveur (paramètre GET `mode`)
- Recherche (côté serveur)
- **Classement équipes** : rang, nom, participants, jours, distance, points, pts/participant
- **Classement individuel** : rang, participant, équipe(s), jours, distance, points
- Ligne de l'utilisateur connecté surlignée + badge "moi"
- Colonnes triables côté serveur (GET `sort` + `dir`)
- Pagination (20/page)
- **Sidebar** : position personnelle, podium top 3 équipes, stats globales (répartition vélo/marche, CO₂)

---

### P04 — Équipes & Participants (`/{citySlug}/communaute`)

**Accès** : public ; actions (rejoindre/créer équipe) nécessitent `ROLE_USER`

**Contenu** :
- Switch Participants (défaut) / Équipes
- **Vue Participants** : liste paginée (10/page), avatar, nom, équipes, dernière activité, jours actifs ; tri par activité récente ; recherche
- **Vue Équipes** : grille 3 colonnes, carte par équipe (avatar, nom, membres, description, stats jours/km/pts par pers.) ; bouton "+ Créer une équipe" (`ROLE_USER`)
- Modal création équipe : upload logo, nom, description, visibilité

---

### P05 — Profil participant (`/{citySlug}/participants/{username}`)

**Accès** : public si `CityEdition.profilesPublic = true`, sinon `ROLE_USER` ; boutons d'édition uniquement sur son propre profil

**Contenu** :
- **Sidebar** : carte identité (avatar, nom, username, dernière activité, niveau cycliste, type de vélo), boutons Modifier profil / Photo (propre profil), équipes avec rang et pts/pers.
- **Contenu principal** :
  - Sélecteur d'édition (années disponibles + "Tout")
  - Stats 3 cases : jours actifs, distance (tags vélo/marche), points (décomposition jours/km/bonus)
  - Heatmap : grille dynamique (nb de cases = durée de l'édition sélectionnée) ; teal moyen = vélo seul, orange moyen = marche seule, teal foncé = les deux, fond = inactif ; jour courant encadré
  - Liste paginée des trajets
  - Tableau historique multi-éditions : Édition, Jours, Distance, Points, CO₂, Rang individuel

---

### P06 — Connexion / Inscription (`/{citySlug}/connexion`, `/{citySlug}/inscription`)

**Accès** : public (redirection si déjà connecté)

**Contenu** :
- Layout deux panneaux : gauche teal (contexte/stats live), droite formulaires
- Switch "Se connecter" / "S'inscrire"
- **Connexion** : email, mot de passe, lien "Mot de passe oublié", rappel accès QR mobile
- **Inscription** : prénom, nom, email, mot de passe (indicateur robustesse), profil cycliste, type de vélo, choix équipe (optionnel), consentement emails
  - L'utilisateur est rattaché à la `City` du `{citySlug}` courant
  - Compte actif immédiatement ; email de bienvenue envoyé
- Note RGPD avec lien mentions légales

---

### P07 — Infos pratiques (`/{citySlug}/infos`)

**Accès** : public

**Contenu** : accordéon 6 sections :
1. **Règles** : période, ce qui compte/ne compte pas, formule des points avec exemple
2. **FAQ** : 7 questions/réponses (accordéon imbriqué)
3. **Défis photo** : grille 2 colonnes, 8 défis avec badge points (depuis `BonusPhotoConfig`), tooltip description, bouton "Soumettre" (→ P11)
4. **Saisie mobile** : 3 étapes + avertissement lien confidentiel
5. **Partenaires** : grille 4 colonnes (depuis `Partner[]`)
6. **Organisation & contact** : infos `City`, réseaux sociaux, lien mentions légales

---

### P08 — Mot de passe oublié (`/mot-de-passe-oublie`)

**Accès** : public (route globale, sans préfixe ville)

**Contenu** :
- Carte centrée avec en-tête teal
- Indicateur 3 étapes : "Votre e-mail" → "Lien envoyé" → "Nouveau mot de passe"
- Étape 1 : saisie e-mail
- Étape 2 : confirmation (e-mail affiché, délai 30 min, lien "Renvoyer")
- Étape 3 : nouveau mot de passe + confirmation + indicateur robustesse

**Actions** : `POST /mot-de-passe-oublie`, `POST /mot-de-passe-reinitialiser/{token}`

---

### P09 — Saisie mobile (`/mobile/{token}`)

**Accès** : via `personalToken` uniquement, sans session (route globale)

**Contenu** (mobile-first, max-width 390px) :
- Mini-stats (jours actifs, km, points)
- Toggle vélo / marche : 2 grands boutons tactiles
- Input distance : boutons − / + (pas 0,5 km, hauteur 56px) + champ numérique
- Sélecteur date : 3 boutons (Aujourd'hui, Hier, Avant-hier) + bouton "Choisir…" révélant un `<input type="date">` ; date contrainte à la période officielle de l'édition
- Bouton Valider (hauteur 52px, pleine largeur)
- Toast de confirmation (2,5 sec)
- Historique des 4 derniers trajets avec suppression
- Bandeau avertissement lien confidentiel

**Sécurité** : accès en lecture/écriture uniquement aux propres trajets de l'utilisateur.

---

### P10 — Détail d'une équipe (`/{citySlug}/equipes/{slug}`)

**Accès** : public

**Contenu** :
- Hero teal : avatar, nom, métadonnées (membres, date création, public/privé), description, bouton Rejoindre/Quitter (`ROLE_USER`)
- Bande stats 4 colonnes : jours, km, points, pts/participant
- Tableau membres classé par points (rang, avatar, nom, dernière activité, km, jours, points)
- Pagination 10 membres/page
- **Sidebar** : rang au classement + barre progression vers la 3e place, bouton Rejoindre, carte "+ Créer une équipe"

**Actions** : `POST /{citySlug}/equipes/{id}/rejoindre`, `DELETE /{citySlug}/equipes/{id}/quitter`

---

### P11 — Défis photo (`/{citySlug}/defis-photo`)

**Accès** : `ROLE_USER` pour soumettre ; galerie publique si `CityEdition.photoChallengesEnabled = true`

**Contenu** :
- Formulaire soumission (en-tête orange) : drag & drop, sélection défi parmi 8 (grille 2 colonnes avec points depuis `BonusPhotoConfig` et tooltip), commentaire, consentement publication
- Galerie paginée des photos approuvées (grille 3 colonnes) avec filtres par défi
- **Sidebar** : mes soumissions avec badge statut, total points bonus, récapitulatif des 8 défis

**Actions** : `POST /{citySlug}/bonus-photos`, `GET /{citySlug}/bonus-photos`

---

### P12 — Édition du profil (`/{citySlug}/mon-profil/modifier`)

**Accès** : `ROLE_USER`

**Contenu** : nav latérale sticky + 6 sections indépendantes (sauvegarde distincte par section) :
1. **Identité** : prénom, nom, email, genre, âge
2. **Profil cycliste** : niveau (radio visuel), type de vélo, bénéfice perçu, principal frein
3. **Photo de profil** : upload avec aperçu, suppression
4. **Mot de passe** : ancien + nouveau + confirmation + indicateur robustesse
5. **Préférences** : consentement emails, profil public, régénération du token mobile (avertissement invalidation de l'ancien lien QR)
6. **Zone de danger** : suppression du compte (confirmation en deux étapes avec saisie du mot de passe)

---

### P13 — Admin ville : Tableau de bord (`/{citySlug}/admin`)

**Accès** : `ROLE_ADMIN_VILLE` (limité à sa ville)

**Contenu** :
- Nav admin sombre distincte avec badge ADMIN, liens sections admin, lien "Voir le site public"
- 4 KPIs avec tendance : participants, jours actifs, distance (détail vélo/marche), photos en attente (orange si > 0)
- 4 raccourcis rapides (Participants, Trajets, Configuration, Partenaires) avec compteurs
- **Graphique d'activité** : barres verticales, nb trajets saisis par jour sur les 14 derniers jours (depuis `Trip.createdAt`)
- **Message des organisateurs** : lecture seule + bouton "Modifier" déployant un textarea (PATCH `CityEdition.organizerMessage`)
- **Validation photos en attente** : liste avec vignette, participant, défi, date soumission ; popin Visualiser (photo pleine taille, métadonnées, saisie des points pré-remplie depuis `BonusPhotoConfig`, boutons Valider/Rejeter) ; actions Valider/Rejeter directes en ligne
- **Statistiques globales** : 3 colonnes (participation vélo/marche/mixte, distance & CO₂, équipes & photos)

---

### P14 — Admin ville : Participants & Équipes (`/{citySlug}/admin/communaute`)

**Accès** : `ROLE_ADMIN_VILLE`

**Contenu** :
- Switch Participants (défaut) / Équipes
- **Tableau participants** : sélection, nom + profil résumé, email, équipes, jours, km, points, actions (voir fiche, modifier, impersonation, supprimer)
- **Tableau équipes** : sélection, nom, membres, jours, km, pts/participant, actions (voir, modifier, supprimer)
- Actions groupées : supprimer, envoyer e-mail, exporter CSV
- Pagination (20/page)
- Modals CRUD : fiche lecture, modifier participant, créer participant (+ envoi email mot de passe temporaire), modifier équipe, créer équipe
- Confirmation suppression dédiée

**Export CSV participants** : Prénom, Nom, Email, Équipes, Jours actifs, Km vélo, Km marche, Points, CO₂ évité

**Impersonation** : mécanisme Symfony `switch_user` (`?_switch_user={email}`), limité à la propre ville de l'admin via un `Voter` dédié. Bandeau orange persistant avec lien "Quitter" (`?_switch_user=_exit`).

---

### P15 — Admin ville : Gestion des trajets (`/{citySlug}/admin/trajets`)

**Accès** : `ROLE_ADMIN_VILLE`

**Contenu** :
- 5 métriques : trajets total, jours-participants, km vélo, km marche, anomalies (badge orange)
- Filtres : recherche participant, mode, équipe, plage de dates, toggle "Anomalies uniquement"
- **Tableau** : sélection, participant, mode, distance, date trajet, date saisie, points, badge anomalie (⚠ Distance suspecte ou ⚠ Hors période), actions (éditer inline, supprimer)
- **Édition inline** : ligne de formulaire dépliée sous le trajet (mode, km, date) ; sauvegarde recalcule `pointsGenerated` et efface le badge si valeurs valides
- Lignes anomalies fond rouge pâle
- Actions groupées : supprimer, exporter CSV — pagination (20/page)

**Export CSV trajets** : Participant, Mode, Distance (km), Date trajet, Date saisie, Points, Anomalie (oui/non)

**Détection anomalies** :
- Distance suspecte : `distanceKm > CityEdition.suspiciousDistanceBike` (vélo) ou `> CityEdition.suspiciousDistanceWalk` (marche)
- Hors période : `tripDate` hors `[Edition.startDate, Edition.endDate]`

---

### P16 — Admin ville : Configuration (`/{citySlug}/admin/configuration`)

**Accès** : `ROLE_ADMIN_VILLE`

**Contenu** : nav latérale sticky + 6 blocs :

1. **Paramètres de participation** : objectif participants, objectif distance (les dates de l'édition sont en lecture seule ici, gérées par le super admin en P19)
2. **Calcul des points** : 3 coefficients éditables (pt/jour, pt/km vélo, pt/km marche), seuils anomalie (km vélo, km marche), formule mise à jour en temps réel (JS côté client) ; avertissement : la sauvegarde déclenche un recalcul en masse des `Trip.pointsGenerated` de la `CityEdition` (batch Doctrine par lots de 500)
3. **Compteur d'étapes** : liste réorderable via boutons ↑ ↓ (nom + distance km), ajout/suppression
4. **Options du site** : 5 toggles (inscriptions ouvertes, saisie trajets ouverte, défis photo activés, classements publics, profils publics)
5. **Points des défis photo** : 8 champs numériques (un par type de défi), avec valeurs par défaut depuis `BonusPhotoConfig` ; sauvegarde met à jour les 8 entrées `BonusPhotoConfig` de la `CityEdition`
6. **Identité de la ville** : nom association, description courte, email contact, lien réseau 1, lien réseau 2, logo (upload)

---

### P17 — Admin ville : Partenaires (`/{citySlug}/admin/partenaires`)

**Accès** : `ROLE_ADMIN_VILLE`

**Contenu** :
- Grille 3 colonnes de cartes partenaires
- Chaque carte : logo (placeholder si absent), nom, URL, boutons ↑ ↓, bouton Modifier ; overlay survol logo : Modifier + Supprimer
- Carte "+ Ajouter" en dernière position
- Modal création/édition : upload logo (drag & drop), nom (obligatoire), URL (optionnel, rend le logo cliquable sur P07), description courte (optionnel)
- Réordonnancement : `PATCH /{citySlug}/admin/partenaires/{id}/position`

---

### P18 — Page 404

**Accès** : public

**Contenu** :
- Navigation standard
- Grand "404" en gris clair, titre "Vous avez fait une fausse route !"
- 3 boutons : Retour à l'accueil, Voir les classements, Se connecter
- Tags cliquables vers les pages populaires

---

### P19 — Super Admin : Gestion globale (`/admin`)

**Accès** : `ROLE_SUPER_ADMIN`

**Contenu** :
- Nav super admin distincte (plus sombre ou différenciée de la nav ville-admin)
- **KPIs globaux** : nb villes actives, nb éditions, nb participants total toutes villes, km totaux

- **CRUD Villes** : tableau des villes (nom, slug, admin(s), nb éditions, statut actif/inactif)
  - Créer ville : nom, slug (validé unique), email contact, description
  - Éditer ville : tous les champs `City`
  - Désactiver/supprimer (avec confirmation)
  - Assigner un `ROLE_ADMIN_VILLE` à une ville (select utilisateur existant ou création)

- **CRUD Éditions** : tableau des éditions (nom, année, dates, nb villes participantes, statut)
  - Créer édition : nom, année, `startDate`, `endDate`, `warmupStartDate` (optionnel), `warmupEndDate` (optionnel)
  - Éditer édition : tous les champs `Edition`
  - **Gestion des villes participantes** : liste des `CityEdition` de l'édition avec bouton "Ajouter une ville" (crée une `CityEdition` avec valeurs par défaut et génère automatiquement les 8 `BonusPhotoConfig`) et bouton "Retirer" (supprime la `CityEdition` si aucun trajet n'a encore été saisi, sinon avertissement)
  - Archiver une édition (passage en lecture seule)

---

## Points d'attention techniques

### Routing multi-villes (path-based)
La ville active est résolue depuis le paramètre `{citySlug}` de la route via un service `CitySlugResolver` injecté dans un `RequestListener`. Toutes les routes ville-spécifiques sont préfixées par `/{citySlug}/`. Les routes globales (`/`, `/admin`, `/mobile/{token}`, `/mot-de-passe-oublie`) n'ont pas ce préfixe. Les queries Doctrine sont filtrées par `city` pour éviter les fuites inter-villes. Les routes admin ville sont protégées par un `CityVoter` vérifiant que l'admin agit sur sa propre ville.

### Entité CityEdition — point central
`CityEdition` remplace les liens directs vers `Edition` dans `Trip`, `BonusPhoto` et `CounterStep`. La `CityEdition` active pour une ville correspond à l'édition dont `Edition.startDate ≤ aujourd'hui ≤ Edition.endDate` (ou la plus récente si le challenge est terminé). Un service `ActiveCityEditionResolver` expose cette logique.

### Calcul des points
Le recalcul est déclenché à chaque modification d'un `Trip`, changement de statut d'un `BonusPhoto`, ou modification des coefficients dans `CityEdition`. Utiliser un `TripPointsCalculator` service dans les listeners Doctrine (`postPersist`, `postUpdate`, `preRemove`). Recalcul en masse (P16 bloc 2) : batch Doctrine par lots de 500.

### BonusPhotoConfig — auto-création
Lors de la création d'une `CityEdition` (par le super admin en P19), un listener `CityEditionCreatedListener` crée automatiquement les 8 entrées `BonusPhotoConfig` avec les valeurs par défaut définies en constante (`BonusPhotoConfig::DEFAULTS`). Lors de la validation d'une photo en P13, la valeur `BonusPhotoConfig.points` est pré-remplie dans le formulaire mais reste modifiable.

### Détection des anomalies
Service `TripAnomalyDetector` via la propriété calculée `Trip::isSuspicious`. Seuils configurés dans `CityEdition`. Les anomalies ne bloquent pas la saisie ; elles sont visibles uniquement en P15.

### CO₂ évité
Service `Co2Calculator` avec constante `COEFFICIENT_KG_PER_KM = 0.193`. Injecté dans P00, P01, P02, P05, P13, exports CSV.

### Impersonation admin
Mécanisme Symfony `switch_user` (paramètre GET `?_switch_user={email}`). Un `SwitchUserVoter` custom limite l'action aux `ROLE_ADMIN_VILLE` agissant sur les utilisateurs de leur propre ville. Bandeau orange persistant pendant l'impersonation avec lien "Quitter" (`?_switch_user=_exit`).

### Uploads
VichUploaderBundle. Stockage local en dev, S3-compatible (Flysystem) en prod. Validation : avatars 2 Mo (JPEG/PNG/WEBP), logos 1 Mo, photos bonus 5 Mo.

### Performance — stats agrégées
Symfony Cache (Redis ou APCu) :
- Stats de la ville courante (P01 carte live) : TTL 1 min
- Classement inter-villes (P00, sidebar P01/P02/P03) : TTL 5 min

Invalider ces entrées lors de la création/suppression d'un `Trip` (listener `TripCacheInvalidator`).

### Emails transactionnels
Transport : API Brevo via le package `symfony/brevo-mailer`. Configuration dans `mailer.yaml` :

```yaml
# config/packages/mailer.yaml
framework:
    mailer:
        dsn: '%env(BREVO_DSN)%'  # brevo+api://KEY@default
```

La variable d'environnement `BREVO_DSN` est de la forme `brevo+api://{API_KEY}@default`.

Chaque email est une classe `Symfony\Component\Mime\Email` ou `TemplatedEmail` (template Twig). L'expéditeur par défaut est défini dans `mailer.yaml` (`from`).

Emails envoyés :
- **Bienvenue** — à l'inscription publique (lien vers le site, rappel du lien QR mobile)
- **Mot de passe temporaire** — si participant créé par admin en P14
- **Réinitialisation mot de passe** — token à usage unique, TTL 30 min
- **Validation photo bonus** — notification avec points attribués
- **Rejet photo bonus** — notification
- **Confirmation changement d'adresse email** — lien envoyé à la nouvelle adresse avant prise d'effet
