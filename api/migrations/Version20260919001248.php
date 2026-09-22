<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260919001248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE deployment_server_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE staging_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE deployment_server (id INT NOT NULL, name VARCHAR(255) NOT NULL, webserver_url VARCHAR(255) DEFAULT NULL, host VARCHAR(255) DEFAULT NULL, port INT DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE staging (id INT NOT NULL, project_id INT NOT NULL, deployment_server_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, environment VARCHAR(100) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, branch VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BD528925166D1F9C ON staging (project_id)');
        $this->addSql('CREATE INDEX IDX_BD528925C21B3CA2 ON staging (deployment_server_id)');
        $this->addSql('ALTER TABLE staging ADD CONSTRAINT FK_BD528925166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE staging ADD CONSTRAINT FK_BD528925C21B3CA2 FOREIGN KEY (deployment_server_id) REFERENCES deployment_server (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE deployment_server_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE staging_id_seq CASCADE');
        $this->addSql('ALTER TABLE staging DROP CONSTRAINT FK_BD528925166D1F9C');
        $this->addSql('ALTER TABLE staging DROP CONSTRAINT FK_BD528925C21B3CA2');
        $this->addSql('DROP TABLE deployment_server');
        $this->addSql('DROP TABLE staging');
    }
}
