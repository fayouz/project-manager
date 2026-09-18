<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918213742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE organisation_member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE team_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE team_member_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE organisation_member (id INT NOT NULL, organisation_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(30) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F2FF20659E6B1585 ON organisation_member (organisation_id)');
        $this->addSql('CREATE INDEX IDX_F2FF2065A76ED395 ON organisation_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ORG_USER ON organisation_member (organisation_id, user_id)');
        $this->addSql('COMMENT ON COLUMN organisation_member.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_member (id INT NOT NULL, project_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(30) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_67401132166D1F9C ON project_member (project_id)');
        $this->addSql('CREATE INDEX IDX_67401132A76ED395 ON project_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_PROJECT_USER ON project_member (project_id, user_id)');
        $this->addSql('COMMENT ON COLUMN project_member.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE team (id INT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C4E0A61F166D1F9C ON team (project_id)');
        $this->addSql('COMMENT ON COLUMN team.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE team_member (id INT NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, role VARCHAR(30) NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6FFBDA1296CD8AE ON team_member (team_id)');
        $this->addSql('CREATE INDEX IDX_6FFBDA1A76ED395 ON team_member (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_TEAM_USER ON team_member (team_id, user_id)');
        $this->addSql('COMMENT ON COLUMN team_member.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE organisation_member ADD CONSTRAINT FK_F2FF20659E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE organisation_member ADD CONSTRAINT FK_F2FF2065A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE team_member ADD CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE organisation_member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_member_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE team_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE team_member_id_seq CASCADE');
        $this->addSql('ALTER TABLE organisation_member DROP CONSTRAINT FK_F2FF20659E6B1585');
        $this->addSql('ALTER TABLE organisation_member DROP CONSTRAINT FK_F2FF2065A76ED395');
        $this->addSql('ALTER TABLE project_member DROP CONSTRAINT FK_67401132166D1F9C');
        $this->addSql('ALTER TABLE project_member DROP CONSTRAINT FK_67401132A76ED395');
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61F166D1F9C');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1296CD8AE');
        $this->addSql('ALTER TABLE team_member DROP CONSTRAINT FK_6FFBDA1A76ED395');
        $this->addSql('DROP TABLE organisation_member');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('DROP TABLE team');
        $this->addSql('DROP TABLE team_member');
    }
}
