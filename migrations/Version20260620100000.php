<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260620100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename ROLE_ADMIN_VILLE to ROLE_ADMIN_CITY in user roles JSON column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE "user"
            SET roles = replace(roles::text, 'ROLE_ADMIN_VILLE', 'ROLE_ADMIN_CITY')::jsonb
            WHERE roles::text LIKE '%ROLE_ADMIN_VILLE%'
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE "user"
            SET roles = replace(roles::text, 'ROLE_ADMIN_CITY', 'ROLE_ADMIN_VILLE')::jsonb
            WHERE roles::text LIKE '%ROLE_ADMIN_CITY%'
        SQL);
    }
}
