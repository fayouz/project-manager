<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add query_regex to ldap_configuration.
 */
final class Version20260919013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add query_regex to ldap_configuration table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ldap_configuration ADD query_regex VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ldap_configuration DROP query_regex');
    }
}
