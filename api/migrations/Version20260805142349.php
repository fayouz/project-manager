<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260805142349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE ldap_configuration_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE ldap_configuration (id INT NOT NULL, enabled BOOLEAN NOT NULL, host VARCHAR(255) NOT NULL, port INT NOT NULL, base_dn VARCHAR(255) NOT NULL, bind_dn VARCHAR(255) DEFAULT NULL, bind_password VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE ldap_configuration_id_seq CASCADE');
        $this->addSql('DROP TABLE ldap_configuration');
    }
}
