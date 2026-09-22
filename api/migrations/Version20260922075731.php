<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922075731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE proxy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE proxy (id INT NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) DEFAULT NULL, no_proxy TEXT DEFAULT NULL, enabled BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN proxy.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN proxy.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE integration ADD proxy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE integration ADD CONSTRAINT FK_FDE96D9BDB26A4E FOREIGN KEY (proxy_id) REFERENCES proxy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_FDE96D9BDB26A4E ON integration (proxy_id)');
        $this->addSql('ALTER TABLE server ADD proxy_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F6DB26A4E FOREIGN KEY (proxy_id) REFERENCES proxy (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5A6DD5F6DB26A4E ON server (proxy_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE integration DROP CONSTRAINT FK_FDE96D9BDB26A4E');
        $this->addSql('ALTER TABLE server DROP CONSTRAINT FK_5A6DD5F6DB26A4E');
        $this->addSql('DROP SEQUENCE proxy_id_seq CASCADE');
        $this->addSql('DROP TABLE proxy');
        $this->addSql('DROP INDEX IDX_FDE96D9BDB26A4E');
        $this->addSql('ALTER TABLE integration DROP proxy_id');
        $this->addSql('DROP INDEX IDX_5A6DD5F6DB26A4E');
        $this->addSql('ALTER TABLE server DROP proxy_id');
    }
}
