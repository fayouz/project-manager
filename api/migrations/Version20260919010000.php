<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add searchFilter, searchFilterRegex, searchFilters to ldap_configuration.
 */
final class Version20260919010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add search_filter, search_filter_regex, and search_filters to ldap_configuration table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ldap_configuration ADD search_filter VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE ldap_configuration ADD search_filter_regex VARCHAR(512) DEFAULT NULL');
        $this->addSql('ALTER TABLE ldap_configuration ADD search_filters JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ldap_configuration DROP search_filter');
        $this->addSql('ALTER TABLE ldap_configuration DROP search_filter_regex');
        $this->addSql('ALTER TABLE ldap_configuration DROP search_filters');
    }
}
