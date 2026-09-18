<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918142223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE project_integration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE project_integration (id INT NOT NULL, project_id INT NOT NULL, integration_id INT NOT NULL, parameters JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_35269A60166D1F9C ON project_integration (project_id)');
        $this->addSql('CREATE INDEX IDX_35269A609E82DDEA ON project_integration (integration_id)');
        $this->addSql('COMMENT ON COLUMN project_integration.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_integration.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_integration ADD CONSTRAINT FK_35269A60166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_integration ADD CONSTRAINT FK_35269A609E82DDEA FOREIGN KEY (integration_id) REFERENCES integration (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE project_integration_id_seq CASCADE');
        $this->addSql('ALTER TABLE project_integration DROP CONSTRAINT FK_35269A60166D1F9C');
        $this->addSql('ALTER TABLE project_integration DROP CONSTRAINT FK_35269A609E82DDEA');
        $this->addSql('DROP TABLE project_integration');
    }
}
