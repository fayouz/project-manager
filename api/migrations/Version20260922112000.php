<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add "Aucune" to server_authentication_type table.
 */
final class Version20260922112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add "Aucune" to server_authentication_type';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO server_authentication_type (id, name) SELECT nextval('server_authentication_type_id_seq'), 'Aucune' WHERE NOT EXISTS (SELECT 1 FROM server_authentication_type WHERE name = 'Aucune')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM server_authentication_type WHERE name = 'Aucune'");
    }
}
