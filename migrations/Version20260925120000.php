<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recompute stored bike trip points so novice cyclists get the x2 points-per-km bonus';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE trip t
            SET points_generated = ROUND((t.distance_km * ce.points_per_km_bike * 2)::numeric, 2)
            FROM city_edition ce, "user" u
            WHERE ce.id = t.city_edition_id
            AND u.id = t.user_id
            AND t.mode = 'bike'
            AND u.cyclist_profile = 'novice'
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE trip t
            SET points_generated = ROUND((t.distance_km * ce.points_per_km_bike)::numeric, 2)
            FROM city_edition ce, "user" u
            WHERE ce.id = t.city_edition_id
            AND u.id = t.user_id
            AND t.mode = 'bike'
            AND u.cyclist_profile = 'novice'
            SQL);
    }
}
