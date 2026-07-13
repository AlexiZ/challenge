-- ============================================================
-- Jeu de données de test — Challenge Mobilité
-- Mot de passe pour tous les comptes : "password"
-- Exécuter dans Adminer ou via psql
-- ============================================================

BEGIN;

TRUNCATE TABLE city, edition, "user" RESTART IDENTITY CASCADE;

-- ============================================================
-- Villes
-- ============================================================
INSERT INTO city (id, name, slug, organization_name, organization_description, contact_email,
    social_link1_type, social_link1_url) VALUES
(1, 'Plabennec', 'plabennec',
    'Commune de Plabennec',
    'Commune du Pays de Brest engagée dans la mobilité douce.',
    'challenge@plabennec.fr',
    'facebook', 'https://www.facebook.com/mairie.plabennec'),
(2, 'Brest', 'brest',
    'Brest Métropole',
    'Brest, ville océane pionnière du vélo urbain.',
    'challenge@brest.fr',
    'instagram', 'https://www.instagram.com/brestmetropole'),
(3, 'Landerneau', 'landerneau',
    'Commune de Landerneau',
    'Landerneau, ville fleurie au bord de l''Elorn.',
    'challenge@landerneau.fr',
    NULL, NULL);

-- ============================================================
-- Éditions
-- ============================================================
INSERT INTO edition (id, name, year, start_date, end_date, warmup_start_date, warmup_end_date) VALUES
(1, 'Édition 2025', 2025, '2025-04-07', '2025-10-05', '2025-03-31', '2025-04-06'),
(2, 'Édition 2026', 2026, '2026-04-06', '2026-10-04', '2026-03-30', '2026-04-05'),
(3, 'Édition 2027', 2027, '2027-04-05', '2027-10-03', NULL, NULL);

-- ============================================================
-- Éditions par ville
-- ============================================================
INSERT INTO city_edition (id, city_id, edition_id,
    target_participants, target_distance_km,
    points_per_day, points_per_km_bike, points_per_km_walk,
    suspicious_distance_bike, suspicious_distance_walk,
    registrations_open, trips_entry_open, photo_challenges_enabled,
    rankings_public, profiles_public, organizer_message) VALUES
-- Plabennec 2026 (active — id=1)
(1, 1, 2, 80, 10000, 1.0, 0.1, 0.2, 100.0, 30.0, true, true, true, true, true,
    'Bienvenue dans l''édition 2026 du Challenge Mobilité Plabennec ! '
    'Cette année, l''objectif est de franchir les 10 000 km collectifs. '
    'Chaque trajet compte — à vélo comme à pied. Merci à toutes et tous pour votre engagement !'),
-- Brest 2026 (active — id=2)
(2, 2, 2, 200, 25000, 1.0, 0.1, 0.2, 120.0, 35.0, true, true, true, true, true,
    'Brest Métropole relève le défi mobilité durable 2026 ! '
    'Cap sur les 25 000 km collectifs. Chaque coup de pédale compte.'),
-- Landerneau 2027 (à venir — id=3)
(3, 3, 3, 50, 5000, 1.0, 0.1, 0.2, 80.0, 25.0, true, false, false, false, false,
    'L''édition 2027 de Landerneau sera lancée au printemps prochain. Inscriptions ouvertes !'),
-- Plabennec 2025 (passée — id=4)
(4, 1, 1, 60, 8000, 1.0, 0.1, 0.2, 100.0, 30.0, false, false, false, true, true, NULL);

-- ============================================================
-- Utilisateurs (mot de passe : "password")
-- ============================================================

-- Administrateurs
INSERT INTO "user" (id, email, roles, password, first_name, last_name, username,
    receive_emails, public_profile, personal_token, city_id) VALUES
(1, 'superadmin@challenge.fr',  '["ROLE_SUPER_ADMIN","ROLE_USER"]',
    '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q',
    'Super', 'Admin', 'superadmin', true, false, gen_random_uuid()::text, 1),
(2, 'admin@plabennec.fr',       '["ROLE_ADMIN_CITY","ROLE_USER"]',
    '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q',
    'Admin', 'Plabennec', 'admin.plabennec', true, false, gen_random_uuid()::text, 1),
(3, 'admin@brest.fr',           '["ROLE_ADMIN_CITY","ROLE_USER"]',
    '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q',
    'Admin', 'Brest', 'admin.brest', true, false, gen_random_uuid()::text, 2),
(4, 'admin@landerneau.fr',      '["ROLE_ADMIN_CITY","ROLE_USER"]',
    '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q',
    'Admin', 'Landerneau', 'admin.landerneau', true, false, gen_random_uuid()::text, 3);

-- Participants Plabennec (id 5–20)
INSERT INTO "user" (id, email, roles, password, first_name, last_name, username,
    receive_emails, public_profile, personal_token, city_id) VALUES
(5,  'marie.dupont@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Marie',     'Dupont',    'marie.dupont',     true,  true,  gen_random_uuid()::text, 1),
(6,  'jean.lecorre@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Jean',      'Lecorre',   'jean.lecorre',     true,  true,  gen_random_uuid()::text, 1),
(7,  'sophie.martin@email.fr',    '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Sophie',    'Martin',    'sophie.martin',    true,  true,  gen_random_uuid()::text, 1),
(8,  'pierre.goas@email.fr',      '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Pierre',    'Goas',      'pierre.goas',      true,  true,  gen_random_uuid()::text, 1),
(9,  'anne.kerambrun@email.fr',   '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Anne',      'Kerambrun', 'anne.kerambrun',   true,  true,  gen_random_uuid()::text, 1),
(10, 'yann.quere@email.fr',       '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Yann',      'Quéré',     'yann.quere',       true,  true,  gen_random_uuid()::text, 1),
(11, 'celine.pouliquen@email.fr', '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Céline',    'Pouliquen', 'celine.pouliquen', true,  true,  gen_random_uuid()::text, 1),
(12, 'thomas.jezequel@email.fr',  '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Thomas',    'Jézéquel',  'thomas.jezequel',  true,  false, gen_random_uuid()::text, 1),
(13, 'isabelle.guyader@email.fr', '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Isabelle',  'Guyader',   'isabelle.guyader', true,  true,  gen_random_uuid()::text, 1),
(14, 'luc.coat@email.fr',         '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Luc',       'Coat',      'luc.coat',         true,  true,  gen_random_uuid()::text, 1),
(15, 'virginie.bihan@email.fr',   '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Virginie',  'Bihan',     'virginie.bihan',   true,  true,  gen_random_uuid()::text, 1),
(16, 'marc.cariou@email.fr',      '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Marc',      'Cariou',    'marc.cariou',      true,  false, gen_random_uuid()::text, 1),
(17, 'nathalie.argall@email.fr',  '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Nathalie',  'Ar Gall',   'nathalie.argall',  true,  true,  gen_random_uuid()::text, 1),
(18, 'kevin.salaun@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Kévin',     'Salaün',    'kevin.salaun',     true,  true,  gen_random_uuid()::text, 1),
(19, 'laure.pennec@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Laure',     'Pennec',    'laure.pennec',     true,  true,  gen_random_uuid()::text, 1),
(20, 'antoine.bras@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Antoine',   'Bras',      'antoine.bras',     true,  false, gen_random_uuid()::text, 1);

-- Participants Brest (id 21–27)
INSERT INTO "user" (id, email, roles, password, first_name, last_name, username,
    receive_emails, public_profile, personal_token, city_id) VALUES
(21, 'erwann.morvan@email.fr',    '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Erwann',    'Morvan',    'erwann.morvan',    true,  true,  gen_random_uuid()::text, 2),
(22, 'melanie.riou@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Mélanie',   'Riou',      'melanie.riou',     true,  true,  gen_random_uuid()::text, 2),
(23, 'herve.calvez@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Hervé',     'Calvez',    'herve.calvez',     true,  true,  gen_random_uuid()::text, 2),
(24, 'sylvie.legall@email.fr',    '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Sylvie',    'Le Gall',   'sylvie.legall',    true,  true,  gen_random_uuid()::text, 2),
(25, 'bernard.crozon@email.fr',   '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Bernard',   'Crozon',    'bernard.crozon',   true,  false, gen_random_uuid()::text, 2),
(26, 'francoise.cadic@email.fr',  '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Françoise', 'Cadic',     'francoise.cadic',  true,  true,  gen_random_uuid()::text, 2),
(27, 'pascal.menez@email.fr',     '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Pascal',    'Menez',     'pascal.menez',     true,  true,  gen_random_uuid()::text, 2);

-- Participants Landerneau (id 28–30)
INSERT INTO "user" (id, email, roles, password, first_name, last_name, username,
    receive_emails, public_profile, personal_token, city_id) VALUES
(28, 'claire.abgrall@email.fr',   '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Claire',    'Abgrall',   'claire.abgrall',   true,  true,  gen_random_uuid()::text, 3),
(29, 'ronan.kervella@email.fr',   '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Ronan',     'Kervella',  'ronan.kervella',   true,  true,  gen_random_uuid()::text, 3),
(30, 'elodie.tanguy@email.fr',    '["ROLE_USER"]', '$2y$12$RDJ7jP2EWa/Y8f2aL.6z.ua12fEVYjBuAA9VkMdy/R0e/ndJ5YZ2q', 'Élodie',    'Tanguy',    'elodie.tanguy',    true,  false, gen_random_uuid()::text, 3);

-- ============================================================
-- Équipes
-- ============================================================
INSERT INTO team (id, city_id, created_by_id, name, slug, description, is_public, created_at) VALUES
(1, 1,  5, 'Les Solos',               'les-solos',               'Les cyclistes qui aiment rouler en liberté !',               true, '2026-04-01 09:00:00'),
(2, 1,  7, 'Météo Plabennec',         'meteo-plabennec',         'On roule par tous les temps, même sous la pluie bretonne.',  true, '2026-04-01 09:15:00'),
(3, 1, 11, 'École Sainte-Anne',       'ecole-sainte-anne',       'Les parents et enseignants de l''école Sainte-Anne.',        true, '2026-04-02 08:30:00'),
(4, 1, 14, 'Plabennec en Transition', 'plabennec-en-transition', 'Collectif pour une mobilité plus douce à Plabennec.',       true, '2026-04-02 10:00:00'),
(5, 1, 18, 'Coolkozh',                'coolkozh',                '"Tête froide" en breton — l''équipe zen de Plabennec.',      true, '2026-04-03 14:00:00'),
(6, 2, 21, 'Vélo Club Brestois',      'velo-club-brestois',      'Le club vélo historique de Brest.',                         true, '2026-04-01 10:00:00'),
(7, 2, 25, 'Les Marcheurs du Ponant', 'marcheurs-du-ponant',     'Brestois qui préfèrent marcher jusqu''au bout du monde.',   true, '2026-04-02 11:00:00'),
(8, 3, 28, 'Pedalo Landerneau',       'pedalo-landerneau',       'Les pédaleurs de Landerneau, prêts pour 2027 !',            true, '2026-05-01 09:00:00');

-- ============================================================
-- Membres des équipes
-- ============================================================
INSERT INTO team_members (team_id, user_id) VALUES
(1, 5),  (1, 6),
(2, 7),  (2, 8),  (2, 9),  (2, 10),
(3, 11), (3, 12), (3, 13),
(4, 14), (4, 15), (4, 16), (4, 17),
(5, 18), (5, 19), (5, 20),
(6, 21), (6, 22), (6, 23), (6, 24),
(7, 25), (7, 26), (7, 27),
(8, 28), (8, 29);

-- ============================================================
-- Inscriptions aux éditions
-- ============================================================

-- Plabennec 2026 (ce_id=1) : users 5–20
INSERT INTO city_edition_participants (city_edition_id, user_id) VALUES
(1, 5),  (1, 6),  (1, 7),  (1, 8),  (1, 9),  (1, 10),
(1, 11), (1, 12), (1, 13), (1, 14), (1, 15),
(1, 16), (1, 17), (1, 18), (1, 19), (1, 20);

-- Brest 2026 (ce_id=2) : users 21–27
INSERT INTO city_edition_participants (city_edition_id, user_id) VALUES
(2, 21), (2, 22), (2, 23), (2, 24), (2, 25), (2, 26), (2, 27);

-- Landerneau 2027 (ce_id=3) : users 28–30
INSERT INTO city_edition_participants (city_edition_id, user_id) VALUES
(3, 28), (3, 29), (3, 30);

-- Plabennec 2025 (ce_id=4) : users 5–12 (anciens participants)
INSERT INTO city_edition_participants (city_edition_id, user_id) VALUES
(4, 5),  (4, 6),  (4, 7),  (4, 8),  (4, 9),  (4, 10), (4, 11), (4, 12);

-- ============================================================
-- Trajets — Plabennec 2026 (city_edition_id=1)
-- points_generated = ROUND(distance_km * 0.1, 2) vélo
--                  = ROUND(distance_km * 0.2, 2) marche
-- ============================================================

-- User 5 — Marie Dupont : cycliste très active (32 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 5, 1
FROM (VALUES
    ('2026-04-07'::date, 15.2), ('2026-04-09', 18.5), ('2026-04-11', 12.0),
    ('2026-04-14', 22.0), ('2026-04-16', 19.5), ('2026-04-18', 14.0),
    ('2026-04-21', 25.0), ('2026-04-23', 20.0), ('2026-04-25', 16.5),
    ('2026-04-28', 23.0), ('2026-04-30', 18.0), ('2026-05-02', 21.0),
    ('2026-05-05', 17.5), ('2026-05-07', 24.0), ('2026-05-09', 19.0),
    ('2026-05-12', 22.5), ('2026-05-14', 15.0), ('2026-05-16', 20.0),
    ('2026-05-19', 18.5), ('2026-05-21', 26.0), ('2026-05-23', 17.0),
    ('2026-05-26', 21.5), ('2026-05-28', 23.0), ('2026-05-30', 19.5),
    ('2026-06-02', 20.0), ('2026-06-04', 16.5), ('2026-06-06', 22.0),
    ('2026-06-09', 18.0), ('2026-06-11', 25.0), ('2026-06-13', 21.0),
    ('2026-06-16', 19.5), ('2026-06-18', 23.0)
) AS t(d, dist);

-- User 6 — Jean Lecorre : marcheur régulier (21 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'walk', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.2, 2), 6, 1
FROM (VALUES
    ('2026-04-08'::date, 6.5), ('2026-04-11', 5.0), ('2026-04-15', 7.5),
    ('2026-04-18', 6.0), ('2026-04-22', 8.0), ('2026-04-25', 5.5),
    ('2026-04-29', 7.0), ('2026-05-03', 6.5), ('2026-05-06', 8.5),
    ('2026-05-10', 5.0), ('2026-05-13', 7.0), ('2026-05-17', 6.0),
    ('2026-05-20', 9.0), ('2026-05-24', 7.5), ('2026-05-27', 6.5),
    ('2026-05-31', 8.0), ('2026-06-03', 7.0), ('2026-06-07', 6.0),
    ('2026-06-10', 8.5), ('2026-06-14', 7.0), ('2026-06-17', 9.0)
) AS t(d, dist);

-- User 7 — Sophie Martin : cycliste régulière (22 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 7, 1
FROM (VALUES
    ('2026-04-07'::date, 10.5), ('2026-04-10', 14.0), ('2026-04-13', 11.5),
    ('2026-04-17', 16.0), ('2026-04-20', 13.5), ('2026-04-24', 15.0),
    ('2026-04-27', 12.0), ('2026-05-01', 17.5), ('2026-05-04', 14.0),
    ('2026-05-08', 13.0), ('2026-05-11', 16.5), ('2026-05-15', 11.0),
    ('2026-05-18', 15.0), ('2026-05-22', 14.5), ('2026-05-25', 18.0),
    ('2026-05-29', 13.5), ('2026-06-01', 16.0), ('2026-06-05', 12.0),
    ('2026-06-08', 14.5), ('2026-06-12', 17.0), ('2026-06-15', 13.0),
    ('2026-06-19', 15.5)
) AS t(d, dist);

-- User 8 — Pierre Goas : cycliste actif + marche (30 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT mode, dist, d, '2026-06-15 10:00:00',
    ROUND(dist * CASE WHEN mode = 'bike' THEN 0.1 ELSE 0.2 END, 2), 8, 1
FROM (VALUES
    ('2026-04-07'::date, 'bike'::text, 18.0), ('2026-04-09', 'bike', 20.5), ('2026-04-12', 'walk', 8.0),
    ('2026-04-14', 'bike', 22.0), ('2026-04-17', 'bike', 16.5), ('2026-04-19', 'walk', 7.5),
    ('2026-04-21', 'bike', 25.0), ('2026-04-24', 'bike', 19.0), ('2026-04-26', 'walk', 9.0),
    ('2026-04-28', 'bike', 21.0), ('2026-05-01', 'bike', 23.5), ('2026-05-03', 'walk', 8.0),
    ('2026-05-05', 'bike', 20.0), ('2026-05-08', 'bike', 18.0), ('2026-05-10', 'bike', 22.0),
    ('2026-05-13', 'bike', 24.0), ('2026-05-15', 'walk', 7.0), ('2026-05-17', 'bike', 21.0),
    ('2026-05-20', 'bike', 19.5), ('2026-05-22', 'bike', 23.0), ('2026-05-24', 'bike', 20.0),
    ('2026-05-27', 'bike', 22.5), ('2026-05-29', 'walk', 8.5), ('2026-06-01', 'bike', 21.0),
    ('2026-06-03', 'bike', 18.0), ('2026-06-06', 'bike', 24.0), ('2026-06-08', 'bike', 20.5),
    ('2026-06-11', 'bike', 19.0), ('2026-06-13', 'bike', 22.0), ('2026-06-16', 'bike', 21.5)
) AS t(d, mode, dist);

-- User 9 — Anne Kerambrun : marcheuse (18 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'walk', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.2, 2), 9, 1
FROM (VALUES
    ('2026-04-08'::date, 7.0), ('2026-04-12', 8.5), ('2026-04-16', 6.5),
    ('2026-04-20', 9.0), ('2026-04-25', 7.5), ('2026-04-29', 8.0),
    ('2026-05-04', 6.0), ('2026-05-08', 9.5), ('2026-05-12', 7.0),
    ('2026-05-16', 8.0), ('2026-05-20', 10.0), ('2026-05-24', 7.5),
    ('2026-05-28', 8.5), ('2026-06-02', 9.0), ('2026-06-06', 7.0),
    ('2026-06-10', 8.5), ('2026-06-15', 9.5), ('2026-06-18', 8.0)
) AS t(d, dist);

-- User 10 — Yann Quéré : cycliste actif (25 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 10, 1
FROM (VALUES
    ('2026-04-07'::date, 20.0), ('2026-04-10', 17.5), ('2026-04-13', 22.0),
    ('2026-04-16', 19.0), ('2026-04-19', 24.0), ('2026-04-22', 21.5),
    ('2026-04-25', 18.0), ('2026-04-28', 23.0), ('2026-05-01', 20.5),
    ('2026-05-04', 19.0), ('2026-05-07', 22.5), ('2026-05-10', 17.0),
    ('2026-05-13', 21.0), ('2026-05-16', 24.5), ('2026-05-19', 20.0),
    ('2026-05-22', 18.5), ('2026-05-25', 23.0), ('2026-05-28', 21.0),
    ('2026-05-31', 19.5), ('2026-06-03', 22.0), ('2026-06-06', 20.0),
    ('2026-06-09', 25.5), ('2026-06-12', 21.0), ('2026-06-15', 19.0),
    ('2026-06-18', 23.5)
) AS t(d, dist);

-- User 11 — Céline Pouliquen : cycliste modérée (15 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 11, 1
FROM (VALUES
    ('2026-04-09'::date,  9.5), ('2026-04-14', 12.0), ('2026-04-19', 10.5),
    ('2026-04-24', 13.0), ('2026-04-29', 11.0), ('2026-05-04', 14.5),
    ('2026-05-09', 10.0), ('2026-05-14', 12.5), ('2026-05-19', 11.5),
    ('2026-05-24', 13.5), ('2026-05-29', 12.0), ('2026-06-03', 10.5),
    ('2026-06-08', 14.0), ('2026-06-13', 11.0), ('2026-06-18', 12.5)
) AS t(d, dist);

-- User 12 — Thomas Jézéquel : mixte (15 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT mode, dist, d, '2026-06-15 10:00:00',
    ROUND(dist * CASE WHEN mode = 'bike' THEN 0.1 ELSE 0.2 END, 2), 12, 1
FROM (VALUES
    ('2026-04-10'::date, 'bike'::text, 11.0), ('2026-04-15', 'walk',  6.5), ('2026-04-20', 'bike', 13.0),
    ('2026-04-25', 'walk',  7.0), ('2026-04-30', 'bike', 12.5), ('2026-05-05', 'walk',  8.0),
    ('2026-05-10', 'bike', 14.0), ('2026-05-15', 'walk',  6.0), ('2026-05-20', 'bike', 13.5),
    ('2026-05-25', 'walk',  7.5), ('2026-05-30', 'bike', 12.0), ('2026-06-04', 'walk',  8.5),
    ('2026-06-09', 'bike', 15.0), ('2026-06-14', 'walk',  7.0), ('2026-06-19', 'bike', 13.0)
) AS t(d, mode, dist);

-- User 13 — Isabelle Guyader : marcheuse (12 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'walk', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.2, 2), 13, 1
FROM (VALUES
    ('2026-04-11'::date, 5.5), ('2026-04-17', 7.0), ('2026-04-23', 6.0),
    ('2026-04-29', 8.0), ('2026-05-05', 5.5), ('2026-05-11', 7.5),
    ('2026-05-17', 6.5), ('2026-05-23', 8.5), ('2026-05-29', 6.0),
    ('2026-06-04', 7.0), ('2026-06-10', 8.0), ('2026-06-16', 6.5)
) AS t(d, dist);

-- User 14 — Luc Coat : cycliste régulier (15 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 14, 1
FROM (VALUES
    ('2026-04-08'::date, 16.0), ('2026-04-13', 18.5), ('2026-04-18', 15.0),
    ('2026-04-23', 20.0), ('2026-04-28', 17.5), ('2026-05-03', 19.0),
    ('2026-05-08', 16.5), ('2026-05-13', 21.0), ('2026-05-18', 18.0),
    ('2026-05-23', 22.0), ('2026-05-28', 17.0), ('2026-06-02', 20.5),
    ('2026-06-07', 18.5), ('2026-06-12', 21.5), ('2026-06-17', 19.0)
) AS t(d, dist);

-- User 15 — Virginie Bihan : mixte (15 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT mode, dist, d, '2026-06-15 10:00:00',
    ROUND(dist * CASE WHEN mode = 'bike' THEN 0.1 ELSE 0.2 END, 2), 15, 1
FROM (VALUES
    ('2026-04-09'::date, 'walk'::text,  7.5), ('2026-04-14', 'bike', 13.0), ('2026-04-19', 'walk',  8.0),
    ('2026-04-24', 'bike', 14.5), ('2026-04-29', 'walk',  7.0), ('2026-05-04', 'bike', 12.5),
    ('2026-05-09', 'walk',  8.5), ('2026-05-14', 'bike', 15.0), ('2026-05-19', 'walk',  7.5),
    ('2026-05-24', 'bike', 13.5), ('2026-05-29', 'walk',  9.0), ('2026-06-03', 'bike', 14.0),
    ('2026-06-08', 'walk',  8.0), ('2026-06-13', 'bike', 16.0), ('2026-06-18', 'walk',  7.5)
) AS t(d, mode, dist);

-- User 16 — Marc Cariou : cycliste peu actif (7 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 16, 1
FROM (VALUES
    ('2026-04-15'::date, 8.0), ('2026-04-25', 10.5), ('2026-05-05', 9.0),
    ('2026-05-15', 11.0), ('2026-05-25', 8.5), ('2026-06-04', 10.0),
    ('2026-06-14', 9.5)
) AS t(d, dist);

-- User 17 — Nathalie Ar Gall : marcheuse (10 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'walk', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.2, 2), 17, 1
FROM (VALUES
    ('2026-04-10'::date, 6.0), ('2026-04-17', 7.5), ('2026-04-24', 5.5),
    ('2026-05-01', 8.0), ('2026-05-08', 6.5), ('2026-05-15', 7.0),
    ('2026-05-22', 8.5), ('2026-05-29', 6.0), ('2026-06-05', 7.5),
    ('2026-06-12', 8.0)
) AS t(d, dist);

-- User 18 — Kévin Salaün : cycliste (11 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 18, 1
FROM (VALUES
    ('2026-04-08'::date, 13.5), ('2026-04-15', 17.0), ('2026-04-22', 14.0),
    ('2026-04-29', 18.5), ('2026-05-06', 15.0), ('2026-05-13', 16.5),
    ('2026-05-20', 14.5), ('2026-05-27', 18.0), ('2026-06-03', 15.5),
    ('2026-06-10', 17.0), ('2026-06-17', 16.0)
) AS t(d, dist);

-- User 19 — Laure Pennec : mixte légère (9 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT mode, dist, d, '2026-06-15 10:00:00',
    ROUND(dist * CASE WHEN mode = 'bike' THEN 0.1 ELSE 0.2 END, 2), 19, 1
FROM (VALUES
    ('2026-04-12'::date, 'bike'::text, 9.0), ('2026-04-20', 'walk', 6.0), ('2026-04-28', 'bike', 10.5),
    ('2026-05-06', 'walk', 7.0), ('2026-05-14', 'bike', 11.0), ('2026-05-22', 'walk',  6.5),
    ('2026-05-30', 'bike', 10.0), ('2026-06-07', 'walk',  7.5), ('2026-06-15', 'bike',  9.5)
) AS t(d, mode, dist);

-- User 20 — Antoine Bras : cycliste peu actif (5 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 20, 1
FROM (VALUES
    ('2026-04-18'::date, 7.5), ('2026-05-02', 9.0), ('2026-05-16', 8.0),
    ('2026-05-30', 10.0), ('2026-06-13', 8.5)
) AS t(d, dist);

-- ============================================================
-- Trajets — Brest 2026 (city_edition_id=2)
-- ============================================================

-- User 21 — Erwann Morvan : cycliste actif (19 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 21, 2
FROM (VALUES
    ('2026-04-07'::date, 19.0), ('2026-04-11', 22.5), ('2026-04-15', 17.5),
    ('2026-04-19', 24.0), ('2026-04-23', 20.0), ('2026-04-27', 22.0),
    ('2026-05-01', 18.5), ('2026-05-05', 25.0), ('2026-05-09', 21.0),
    ('2026-05-13', 23.5), ('2026-05-17', 19.5), ('2026-05-21', 22.0),
    ('2026-05-25', 20.5), ('2026-05-29', 24.0), ('2026-06-02', 21.5),
    ('2026-06-06', 23.0), ('2026-06-10', 20.0), ('2026-06-14', 22.5),
    ('2026-06-18', 19.0)
) AS t(d, dist);

-- User 22 — Mélanie Riou : mixte (15 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT mode, dist, d, '2026-06-15 10:00:00',
    ROUND(dist * CASE WHEN mode = 'bike' THEN 0.1 ELSE 0.2 END, 2), 22, 2
FROM (VALUES
    ('2026-04-08'::date, 'bike'::text, 14.0), ('2026-04-13', 'walk',  8.0), ('2026-04-18', 'bike', 16.5),
    ('2026-04-23', 'walk',  9.0), ('2026-04-28', 'bike', 15.0), ('2026-05-03', 'walk',  8.5),
    ('2026-05-08', 'bike', 17.0), ('2026-05-13', 'walk',  9.5), ('2026-05-18', 'bike', 14.5),
    ('2026-05-23', 'walk',  8.0), ('2026-05-28', 'bike', 16.0), ('2026-06-02', 'walk',  9.0),
    ('2026-06-07', 'bike', 15.5), ('2026-06-12', 'walk',  8.5), ('2026-06-17', 'bike', 14.0)
) AS t(d, mode, dist);

-- User 23 — Hervé Calvez : cycliste très actif (22 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 23, 2
FROM (VALUES
    ('2026-04-07'::date, 25.0), ('2026-04-09', 28.0), ('2026-04-12', 22.5),
    ('2026-04-14', 30.0), ('2026-04-17', 26.0), ('2026-04-19', 24.5),
    ('2026-04-21', 29.0), ('2026-04-24', 27.0), ('2026-04-26', 23.5),
    ('2026-04-28', 31.0), ('2026-05-01', 25.5), ('2026-05-03', 28.0),
    ('2026-05-05', 26.5), ('2026-05-07', 24.0), ('2026-05-10', 29.5),
    ('2026-05-12', 27.0), ('2026-05-15', 25.0), ('2026-05-17', 30.0),
    ('2026-05-19', 26.0), ('2026-05-22', 28.5), ('2026-05-24', 24.5),
    ('2026-05-27', 32.0)
) AS t(d, dist);

-- User 24 — Sylvie Le Gall : marcheuse (15 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'walk', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.2, 2), 24, 2
FROM (VALUES
    ('2026-04-09'::date,  8.5), ('2026-04-14', 10.0), ('2026-04-19',  7.5),
    ('2026-04-24', 11.0), ('2026-04-29',  9.0), ('2026-05-04', 10.5),
    ('2026-05-09',  8.0), ('2026-05-14', 11.5), ('2026-05-19',  9.5),
    ('2026-05-24', 10.0), ('2026-05-29',  8.5), ('2026-06-03', 11.0),
    ('2026-06-08',  9.0), ('2026-06-13', 10.5), ('2026-06-18',  9.5)
) AS t(d, dist);

-- User 25 — Bernard Crozon : marcheur (10 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'walk', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.2, 2), 25, 2
FROM (VALUES
    ('2026-04-10'::date, 6.0), ('2026-04-17', 7.5), ('2026-04-24', 5.5),
    ('2026-05-01', 8.0), ('2026-05-08', 6.5), ('2026-05-15', 7.0),
    ('2026-05-22', 8.5), ('2026-05-29', 6.0), ('2026-06-05', 7.5),
    ('2026-06-12', 8.0)
) AS t(d, dist);

-- User 26 — Françoise Cadic : cycliste modérée (10 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 26, 2
FROM (VALUES
    ('2026-04-12'::date, 11.0), ('2026-04-19', 13.5), ('2026-04-26', 10.5),
    ('2026-05-03', 14.0), ('2026-05-10', 12.0), ('2026-05-17', 13.5),
    ('2026-05-24', 11.5), ('2026-05-31', 14.5), ('2026-06-07', 12.5),
    ('2026-06-14', 13.0)
) AS t(d, dist);

-- User 27 — Pascal Menez : cycliste régulier (12 trajets)
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT 'bike', dist, d, '2026-06-15 10:00:00', ROUND(dist * 0.1, 2), 27, 2
FROM (VALUES
    ('2026-04-08'::date, 16.0), ('2026-04-14', 19.5), ('2026-04-20', 17.0),
    ('2026-04-26', 21.0), ('2026-05-02', 18.5), ('2026-05-08', 20.0),
    ('2026-05-14', 17.5), ('2026-05-20', 22.0), ('2026-05-26', 19.0),
    ('2026-06-01', 20.5), ('2026-06-07', 18.0), ('2026-06-13', 21.0)
) AS t(d, dist);

-- ============================================================
-- Trajets — Plabennec 2025 (city_edition_id=4, saison passée)
-- ============================================================
INSERT INTO trip (mode, distance_km, trip_date, created_at, points_generated, user_id, city_edition_id)
SELECT mode, dist, d, '2025-10-01 10:00:00',
    ROUND(dist * CASE WHEN mode = 'bike' THEN 0.1 ELSE 0.2 END, 2), uid, 4
FROM (VALUES
    -- User 5
    ('2025-04-14'::date, 'bike'::text, 14.0, 5), ('2025-04-21', 'bike', 18.5, 5),
    ('2025-05-05', 'bike', 20.0, 5), ('2025-06-02', 'bike', 16.5, 5),
    ('2025-07-07', 'bike', 22.0, 5), ('2025-08-11', 'bike', 19.0, 5),
    -- User 6
    ('2025-04-15', 'walk',  6.0, 6), ('2025-04-22', 'walk',  7.5, 6),
    ('2025-05-06', 'walk',  8.0, 6), ('2025-06-10', 'walk',  6.5, 6),
    -- User 7
    ('2025-04-14', 'bike', 11.0, 7), ('2025-04-28', 'bike', 13.5, 7),
    ('2025-05-12', 'bike', 12.0, 7), ('2025-06-09', 'bike', 14.0, 7),
    -- User 8
    ('2025-04-16', 'bike', 19.0, 8), ('2025-04-30', 'bike', 22.0, 8),
    ('2025-05-14', 'bike', 20.5, 8), ('2025-06-18', 'bike', 18.0, 8),
    -- User 9
    ('2025-04-17', 'walk',  7.0, 9), ('2025-05-01', 'walk',  8.5, 9),
    ('2025-06-05', 'walk',  9.0, 9),
    -- User 10
    ('2025-04-15', 'bike', 18.0, 10), ('2025-04-29', 'bike', 21.0, 10),
    ('2025-05-13', 'bike', 19.5, 10), ('2025-06-17', 'bike', 23.0, 10)
) AS t(d, mode, dist, uid);

-- ============================================================
-- Configurations de bonus photo — Plabennec 2026 (ce_id=1)
-- ============================================================
INSERT INTO bonus_photo_config (id, city_edition_id, challenge, points) VALUES
(1,  1, 'le_parrain',          5.0),
(2,  1, 'panier_garni',        3.0),
(3,  1, 'classe_a_plach',      4.0),
(4,  1, 'pour_le_plaisir',     2.0),
(5,  1, 'famille_nombreuse',   4.0),
(6,  1, 'tshirt_mouille',      3.0),
(7,  1, 'esprit_equipe',       5.0),
(8,  1, 'panoramacyclette',    3.0),
-- Brest 2026 (ce_id=2)
(9,  2, 'le_parrain',          5.0),
(10, 2, 'panier_garni',        3.0),
(11, 2, 'esprit_equipe',       5.0),
(12, 2, 'panoramacyclette',    3.0);

-- ============================================================
-- Bonus photos
-- ============================================================
INSERT INTO bonus_photo (city_edition_id, user_id, reviewed_by_id, challenge, status,
    points_awarded, consent_to_publish, submitted_at, reviewed_at) VALUES
-- Plabennec 2026 — approuvées
(1,  5, 2, 'le_parrain',        'approved',  5.0, true,  '2026-04-20 14:30:00', '2026-04-21 10:00:00'),
(1,  5, 2, 'panoramacyclette',  'approved',  3.0, true,  '2026-05-10 18:00:00', '2026-05-11 09:00:00'),
(1,  7, 2, 'esprit_equipe',     'approved',  5.0, true,  '2026-04-25 12:00:00', '2026-04-26 10:00:00'),
(1,  8, 2, 'panier_garni',      'approved',  3.0, false, '2026-05-02 09:00:00', '2026-05-03 10:00:00'),
(1,  8, 2, 'tshirt_mouille',    'approved',  3.0, true,  '2026-05-15 17:00:00', '2026-05-16 09:00:00'),
(1, 10, 2, 'le_parrain',        'approved',  5.0, true,  '2026-04-22 11:00:00', '2026-04-23 10:00:00'),
(1, 10, 2, 'classe_a_plach',    'approved',  4.0, true,  '2026-05-20 16:00:00', '2026-05-21 10:00:00'),
(1, 14, 2, 'famille_nombreuse', 'approved',  4.0, true,  '2026-05-05 10:30:00', '2026-05-06 09:00:00'),
(1, 18, 2, 'pour_le_plaisir',   'approved',  2.0, false, '2026-04-30 15:00:00', '2026-05-01 10:00:00'),
-- Plabennec 2026 — en attente de validation
(1, 11, NULL, 'panoramacyclette', 'pending', NULL, true,  '2026-06-10 14:00:00', NULL),
(1, 15, NULL, 'esprit_equipe',    'pending', NULL, true,  '2026-06-12 16:00:00', NULL),
-- Brest 2026 — approuvées
(2, 21, 3, 'le_parrain',        'approved',  5.0, true,  '2026-04-18 11:00:00', '2026-04-19 10:00:00'),
(2, 23, 3, 'panoramacyclette',  'approved',  3.0, true,  '2026-04-30 18:00:00', '2026-05-01 09:00:00'),
(2, 23, 3, 'esprit_equipe',     'approved',  5.0, true,  '2026-05-12 12:00:00', '2026-05-13 10:00:00');

-- ============================================================
-- Étapes du compteur — Plabennec 2026
-- ============================================================
INSERT INTO counter_step (id, city_edition_id, name, distance_km, position) VALUES
(1, 1, 'Plabennec → Brest',      25,  1),
(2, 1, 'Brest → Landerneau',     30,  2),
(3, 1, 'Landerneau → Quimper',  100,  3),
(4, 1, 'Quimper → Vannes',       90,  4),
(5, 1, 'Vannes → Rennes',       110,  5),
(6, 1, 'Rennes → Paris',        350,  6);

-- ============================================================
-- Partenaires
-- ============================================================
INSERT INTO partner (id, city_id, name, website_url, description, position) VALUES
(1, 1, 'Décathlon Brest',   NULL, 'Équipementier sportif officiel du challenge Plabennec.', 1),
(2, 1, 'Café de la Mairie', NULL, 'Partenaire café à Plabennec.',                           2),
(3, 2, 'Cykleo Brest',      NULL, 'Service de vélos en libre-service à Brest.',             1),
(4, 2, 'Le Télégramme',     NULL, 'Partenaire média officiel.',                             2);

-- ============================================================
-- Remise à zéro des séquences
-- ============================================================
SELECT setval(pg_get_serial_sequence('city',               'id'), (SELECT MAX(id) FROM city));
SELECT setval(pg_get_serial_sequence('edition',            'id'), (SELECT MAX(id) FROM edition));
SELECT setval(pg_get_serial_sequence('city_edition',       'id'), (SELECT MAX(id) FROM city_edition));
SELECT setval(pg_get_serial_sequence('"user"',             'id'), (SELECT MAX(id) FROM "user"));
SELECT setval(pg_get_serial_sequence('team',               'id'), (SELECT MAX(id) FROM team));
SELECT setval(pg_get_serial_sequence('trip',               'id'), (SELECT MAX(id) FROM trip));
SELECT setval(pg_get_serial_sequence('bonus_photo_config', 'id'), (SELECT MAX(id) FROM bonus_photo_config));
SELECT setval(pg_get_serial_sequence('bonus_photo',        'id'), (SELECT MAX(id) FROM bonus_photo));
SELECT setval(pg_get_serial_sequence('counter_step',       'id'), (SELECT MAX(id) FROM counter_step));
SELECT setval(pg_get_serial_sequence('partner',            'id'), (SELECT MAX(id) FROM partner));

COMMIT;
