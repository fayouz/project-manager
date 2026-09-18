<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918150528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migration pour la gestion STI User (LocalUser et LdapUser)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD discr VARCHAR(255) DEFAULT \'local\'');
        $this->addSql('UPDATE "user" SET discr = CASE WHEN is_ldap = true THEN \'ldap\' ELSE \'local\' END');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN discr SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN discr DROP DEFAULT');

        $this->addSql('ALTER TABLE "user" ADD ldap_uid VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD distinguished_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD synced_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP is_ldap');
        $this->addSql('ALTER TABLE "user" ALTER password DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN "user".synced_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ADD is_ldap BOOLEAN DEFAULT false');
        $this->addSql('UPDATE "user" SET is_ldap = CASE WHEN discr = \'ldap\' THEN true ELSE false END');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_ldap SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_ldap DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" DROP discr');
        $this->addSql('ALTER TABLE "user" DROP ldap_uid');
        $this->addSql('ALTER TABLE "user" DROP distinguished_name');
        $this->addSql('ALTER TABLE "user" DROP synced_at');
        $this->addSql('ALTER TABLE "user" ALTER password SET NOT NULL');
    }
}
