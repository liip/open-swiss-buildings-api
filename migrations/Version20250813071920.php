<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250813071920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an index to improve updating colliding resolver tasks';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX building_entrance_matching_hash_idx ON resolver_task (matching_unique_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_matching_hash_idx');
    }
}
