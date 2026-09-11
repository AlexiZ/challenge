<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add per-edition bike/walk transport mode toggles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE edition ADD bike_mode_enabled BOOLEAN NOT NULL DEFAULT true');
        $this->addSql('ALTER TABLE edition ADD walk_mode_enabled BOOLEAN NOT NULL DEFAULT true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE edition DROP bike_mode_enabled');
        $this->addSql('ALTER TABLE edition DROP walk_mode_enabled');
    }
}
