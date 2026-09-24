<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add an optional "url" field to staging, so a preview of the web page can be
 * produced even when the environment is not tied to a git branch.
 */
final class Version20260923160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional url column to staging table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staging ADD url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE staging DROP url');
    }
}
