<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111195336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bonus_photo (id INT AUTO_INCREMENT NOT NULL, challenge VARCHAR(255) NOT NULL, photo VARCHAR(255) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, consent_to_publish TINYINT NOT NULL, status VARCHAR(255) NOT NULL, points_awarded DOUBLE PRECISION DEFAULT NULL, submitted_at DATETIME NOT NULL, reviewed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, city_edition_id INT NOT NULL, reviewed_by_id INT DEFAULT NULL, INDEX IDX_C10F07BBA76ED395 (user_id), INDEX IDX_C10F07BB1951AAA9 (city_edition_id), INDEX IDX_C10F07BBFC6B21F1 (reviewed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bonus_photo_config (id INT AUTO_INCREMENT NOT NULL, challenge VARCHAR(255) NOT NULL, points DOUBLE PRECISION NOT NULL, enabled TINYINT DEFAULT 1 NOT NULL, city_edition_id INT NOT NULL, INDEX IDX_5E6FFDAF1951AAA9 (city_edition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, organization_name VARCHAR(200) DEFAULT NULL, organization_description LONGTEXT DEFAULT NULL, organization_logo VARCHAR(255) DEFAULT NULL, contact_email VARCHAR(180) DEFAULT NULL, social_link1_type VARCHAR(255) DEFAULT NULL, social_link1_url VARCHAR(255) DEFAULT NULL, social_link2_type VARCHAR(255) DEFAULT NULL, social_link2_url VARCHAR(255) DEFAULT NULL, primary_color VARCHAR(7) DEFAULT NULL, secondary_color VARCHAR(7) DEFAULT NULL, UNIQUE INDEX UNIQ_2D5B0234989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE city_edition (id INT AUTO_INCREMENT NOT NULL, target_participants INT NOT NULL, target_distance_km INT NOT NULL, suspicious_distance_bike DOUBLE PRECISION NOT NULL, suspicious_distance_walk DOUBLE PRECISION NOT NULL, registrations_open TINYINT NOT NULL, trips_entry_open TINYINT NOT NULL, photo_challenges_enabled TINYINT NOT NULL, rankings_public TINYINT NOT NULL, profiles_public TINYINT NOT NULL, organizer_message LONGTEXT DEFAULT NULL, city_id INT NOT NULL, edition_id INT NOT NULL, INDEX IDX_15D9A64D8BAC62AF (city_id), INDEX IDX_15D9A64D74281A5E (edition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE city_edition_participants (city_edition_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_F3CD6E841951AAA9 (city_edition_id), INDEX IDX_F3CD6E84A76ED395 (user_id), PRIMARY KEY (city_edition_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE counter_step (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, distance_km INT NOT NULL, position INT NOT NULL, city_edition_id INT NOT NULL, INDEX IDX_C34275B1951AAA9 (city_edition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE edition (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, year INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, warmup_start_date DATE DEFAULT NULL, warmup_end_date DATE DEFAULT NULL, bike_mode_enabled TINYINT DEFAULT 1 NOT NULL, walk_mode_enabled TINYINT DEFAULT 1 NOT NULL, points_per_day DOUBLE PRECISION NOT NULL, points_per_km_bike DOUBLE PRECISION NOT NULL, points_per_km_walk DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE partner (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, website_url VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, position INT NOT NULL, city_id INT NOT NULL, INDEX IDX_312B3E168BAC62AF (city_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, is_public TINYINT NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, city_id INT NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_C4E0A61F8BAC62AF (city_id), INDEX IDX_C4E0A61FB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE team_members (team_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_BAD9A3C8296CD8AE (team_id), INDEX IDX_BAD9A3C8A76ED395 (user_id), PRIMARY KEY (team_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trip (id INT AUTO_INCREMENT NOT NULL, mode VARCHAR(255) NOT NULL, distance_km DOUBLE PRECISION NOT NULL, trip_date DATE NOT NULL, created_at DATETIME NOT NULL, points_generated DOUBLE PRECISION NOT NULL, user_id INT NOT NULL, city_edition_id INT NOT NULL, INDEX IDX_7656F53BA76ED395 (user_id), INDEX IDX_7656F53B1951AAA9 (city_edition_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, username VARCHAR(100) NOT NULL, cyclist_profile VARCHAR(255) DEFAULT NULL, bike_type VARCHAR(255) DEFAULT NULL, perceived_benefit VARCHAR(255) DEFAULT NULL, main_barrier VARCHAR(255) DEFAULT NULL, gender VARCHAR(255) DEFAULT NULL, age INT DEFAULT NULL, receive_emails TINYINT NOT NULL, public_profile TINYINT NOT NULL, avatar VARCHAR(255) DEFAULT NULL, personal_token VARCHAR(36) NOT NULL, city_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D64976EE72D (personal_token), INDEX IDX_8D93D6498BAC62AF (city_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bonus_photo ADD CONSTRAINT FK_C10F07BBA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bonus_photo ADD CONSTRAINT FK_C10F07BB1951AAA9 FOREIGN KEY (city_edition_id) REFERENCES city_edition (id)');
        $this->addSql('ALTER TABLE bonus_photo ADD CONSTRAINT FK_C10F07BBFC6B21F1 FOREIGN KEY (reviewed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bonus_photo_config ADD CONSTRAINT FK_5E6FFDAF1951AAA9 FOREIGN KEY (city_edition_id) REFERENCES city_edition (id)');
        $this->addSql('ALTER TABLE city_edition ADD CONSTRAINT FK_15D9A64D8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE city_edition ADD CONSTRAINT FK_15D9A64D74281A5E FOREIGN KEY (edition_id) REFERENCES edition (id)');
        $this->addSql('ALTER TABLE city_edition_participants ADD CONSTRAINT FK_F3CD6E841951AAA9 FOREIGN KEY (city_edition_id) REFERENCES city_edition (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE city_edition_participants ADD CONSTRAINT FK_F3CD6E84A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE counter_step ADD CONSTRAINT FK_C34275B1951AAA9 FOREIGN KEY (city_edition_id) REFERENCES city_edition (id)');
        $this->addSql('ALTER TABLE partner ADD CONSTRAINT FK_312B3E168BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F8BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61FB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_members ADD CONSTRAINT FK_BAD9A3C8A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53B1951AAA9 FOREIGN KEY (city_edition_id) REFERENCES city_edition (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6498BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bonus_photo DROP FOREIGN KEY FK_C10F07BBA76ED395');
        $this->addSql('ALTER TABLE bonus_photo DROP FOREIGN KEY FK_C10F07BB1951AAA9');
        $this->addSql('ALTER TABLE bonus_photo DROP FOREIGN KEY FK_C10F07BBFC6B21F1');
        $this->addSql('ALTER TABLE bonus_photo_config DROP FOREIGN KEY FK_5E6FFDAF1951AAA9');
        $this->addSql('ALTER TABLE city_edition DROP FOREIGN KEY FK_15D9A64D8BAC62AF');
        $this->addSql('ALTER TABLE city_edition DROP FOREIGN KEY FK_15D9A64D74281A5E');
        $this->addSql('ALTER TABLE city_edition_participants DROP FOREIGN KEY FK_F3CD6E841951AAA9');
        $this->addSql('ALTER TABLE city_edition_participants DROP FOREIGN KEY FK_F3CD6E84A76ED395');
        $this->addSql('ALTER TABLE counter_step DROP FOREIGN KEY FK_C34275B1951AAA9');
        $this->addSql('ALTER TABLE partner DROP FOREIGN KEY FK_312B3E168BAC62AF');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61F8BAC62AF');
        $this->addSql('ALTER TABLE team DROP FOREIGN KEY FK_C4E0A61FB03A8386');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8296CD8AE');
        $this->addSql('ALTER TABLE team_members DROP FOREIGN KEY FK_BAD9A3C8A76ED395');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BA76ED395');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53B1951AAA9');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6498BAC62AF');
        $this->addSql('DROP TABLE bonus_photo');
        $this->addSql('DROP TABLE bonus_photo_config');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE city_edition');
        $this->addSql('DROP TABLE city_edition_participants');
        $this->addSql('DROP TABLE counter_step');
        $this->addSql('DROP TABLE edition');
        $this->addSql('DROP TABLE partner');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_members');
        $this->addSql('DROP TABLE trip');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
