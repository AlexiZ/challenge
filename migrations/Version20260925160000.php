<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move points scale (per day, per km bike/walk) from city_edition to edition';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE edition ADD points_per_day DOUBLE PRECISION DEFAULT 1.0 NOT NULL');
        $this->addSql('ALTER TABLE edition ADD points_per_km_bike DOUBLE PRECISION DEFAULT 0.1 NOT NULL');
        $this->addSql('ALTER TABLE edition ADD points_per_km_walk DOUBLE PRECISION DEFAULT 0.2 NOT NULL');
        // Reprend le barème de la plus ancienne participation de chaque édition
        $this->addSql('UPDATE edition e SET points_per_day = ce.points_per_day, points_per_km_bike = ce.points_per_km_bike, points_per_km_walk = ce.points_per_km_walk
            FROM (SELECT DISTINCT ON (edition_id) edition_id, points_per_day, points_per_km_bike, points_per_km_walk FROM city_edition ORDER BY edition_id, id) ce
            WHERE ce.edition_id = e.id');
        $this->addSql('ALTER TABLE city_edition DROP points_per_day');
        $this->addSql('ALTER TABLE city_edition DROP points_per_km_bike');
        $this->addSql('ALTER TABLE city_edition DROP points_per_km_walk');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE city_edition ADD points_per_day DOUBLE PRECISION DEFAULT 1.0 NOT NULL');
        $this->addSql('ALTER TABLE city_edition ADD points_per_km_bike DOUBLE PRECISION DEFAULT 0.1 NOT NULL');
        $this->addSql('ALTER TABLE city_edition ADD points_per_km_walk DOUBLE PRECISION DEFAULT 0.2 NOT NULL');
        $this->addSql('UPDATE city_edition ce SET points_per_day = e.points_per_day, points_per_km_bike = e.points_per_km_bike, points_per_km_walk = e.points_per_km_walk FROM edition e WHERE e.id = ce.edition_id');
        $this->addSql('ALTER TABLE edition DROP points_per_day');
        $this->addSql('ALTER TABLE edition DROP points_per_km_bike');
        $this->addSql('ALTER TABLE edition DROP points_per_km_walk');
    }
}
