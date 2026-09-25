<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260925140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add enabled flag on bonus_photo_config to toggle photo challenges per city edition';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bonus_photo_config ADD enabled BOOLEAN DEFAULT true NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bonus_photo_config DROP enabled');
    }
}
