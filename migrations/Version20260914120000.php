<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260914120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill city_edition_participants from existing trips (users with trips but never added as participant)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO city_edition_participants (city_edition_id, user_id)
            SELECT DISTINCT t.city_edition_id, t.user_id
            FROM trip t
            WHERE NOT EXISTS (
                SELECT 1 FROM city_edition_participants p
                WHERE p.user_id = t.user_id AND p.city_edition_id = t.city_edition_id
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Data backfill, not reversible: rows added here are indistinguishable
        // from rows a user could have been legitimately added to since.
    }
}
