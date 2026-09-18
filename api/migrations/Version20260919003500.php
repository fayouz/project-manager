<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add LDAP user details: firstName, lastName, displayName, title, department, managerDn.
 */
final class Version20260919003500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add firstName, lastName, displayName, title, department, managerDn to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD display_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD department VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD manager_dn VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP first_name');
        $this->addSql('ALTER TABLE "user" DROP last_name');
        $this->addSql('ALTER TABLE "user" DROP display_name');
        $this->addSql('ALTER TABLE "user" DROP title');
        $this->addSql('ALTER TABLE "user" DROP department');
        $this->addSql('ALTER TABLE "user" DROP manager_dn');
    }
}
