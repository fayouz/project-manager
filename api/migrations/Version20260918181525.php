<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918181525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project_integration ADD status VARCHAR(30) DEFAULT \'unknown\' NOT NULL');
        $this->addSql('ALTER TABLE project_integration ADD status_message TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE project_integration ADD last_checked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN project_integration.last_checked_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE project_integration DROP status');
        $this->addSql('ALTER TABLE project_integration DROP status_message');
        $this->addSql('ALTER TABLE project_integration DROP last_checked_at');
    }
}
