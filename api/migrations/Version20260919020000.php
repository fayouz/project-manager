<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add manager relation (manager_id) to user table.
 */
final class Version20260919020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manager_id foreign key relation to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD manager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649783E3463 FOREIGN KEY (manager_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8D93D649783E3463 ON "user" (manager_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649783E3463');
        $this->addSql('DROP INDEX IDX_8D93D649783E3463');
        $this->addSql('ALTER TABLE "user" DROP manager_id');
    }
}
