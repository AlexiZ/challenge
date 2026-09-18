<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260918120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill city_edition_participants for registered users with no trips yet (participant = registered, not just trip-havers)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO city_edition_participants (city_edition_id, user_id)
            SELECT resolved.id, u.id
            FROM "user" u
            CROSS JOIN LATERAL (
                SELECT ce.id
                FROM city_edition ce
                JOIN edition e ON e.id = ce.edition_id
                WHERE ce.city_id = u.city_id
                ORDER BY
                    (CURRENT_DATE BETWEEN e.start_date AND e.end_date) DESC,
                    e.year DESC,
                    e.start_date DESC
                LIMIT 1
            ) resolved
            WHERE u.city_id IS NOT NULL
            AND NOT EXISTS (
                SELECT 1 FROM city_edition_participants p
                WHERE p.user_id = u.id AND p.city_edition_id = resolved.id
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        // Data backfill, not reversible: rows added here are indistinguishable
        // from rows a user could have been legitimately added to since.
    }
}
