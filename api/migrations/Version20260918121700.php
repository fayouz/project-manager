<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918121700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE server_authentication_type_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE server_authentication_type (id INT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE integration DROP CONSTRAINT FK_FDE96D9B1844E6B7');
        $this->addSql('ALTER TABLE integration DROP configuration');
        $this->addSql('DELETE FROM integration WHERE server_id IS NULL');
        $this->addSql('ALTER TABLE integration ALTER server_id SET NOT NULL');
        $this->addSql('ALTER TABLE integration ADD CONSTRAINT FK_FDE96D9B1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE server ADD authentication_type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F6FAB00972 FOREIGN KEY (authentication_type_id) REFERENCES server_authentication_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5A6DD5F6FAB00972 ON server (authentication_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE server DROP CONSTRAINT FK_5A6DD5F6FAB00972');
        $this->addSql('DROP SEQUENCE server_authentication_type_id_seq CASCADE');
        $this->addSql('DROP TABLE server_authentication_type');
        $this->addSql('ALTER TABLE integration DROP CONSTRAINT fk_fde96d9b1844e6b7');
        $this->addSql('ALTER TABLE integration ADD configuration JSON NOT NULL');
        $this->addSql('ALTER TABLE integration ALTER server_id DROP NOT NULL');
        $this->addSql('ALTER TABLE integration ADD CONSTRAINT fk_fde96d9b1844e6b7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX IDX_5A6DD5F6FAB00972');
        $this->addSql('ALTER TABLE server DROP authentication_type_id');
    }
}
