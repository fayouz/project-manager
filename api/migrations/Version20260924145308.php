<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924145308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_log table for auditing entity changes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE activity_log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE activity_log (id INT NOT NULL, actor_id INT DEFAULT NULL, entity_type VARCHAR(50) NOT NULL, entity_id VARCHAR(255) NOT NULL, entity_label VARCHAR(255) NOT NULL, action VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FD06F64710DAF24A ON activity_log (actor_id)');
        $this->addSql('CREATE INDEX idx_activity_log_entity ON activity_log (entity_type, entity_id)');
        $this->addSql('CREATE INDEX idx_activity_log_created_at ON activity_log (created_at)');
        $this->addSql('COMMENT ON COLUMN activity_log.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F64710DAF24A FOREIGN KEY (actor_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE activity_log_id_seq CASCADE');
        $this->addSql('ALTER TABLE activity_log DROP CONSTRAINT FK_FD06F64710DAF24A');
        $this->addSql('DROP TABLE activity_log');
    }
}
