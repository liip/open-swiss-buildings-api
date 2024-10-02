<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240412155911 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix resolver address relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_address_id_idx');
        $this->addSql('ALTER TABLE resolver_task ADD matching_address_ids JSON DEFAULT NULL');
        $this->addSql('UPDATE resolver_task SET matching_address_ids = JSONB_BUILD_ARRAY(matching_address_id)');
        $this->addSql('ALTER TABLE resolver_task DROP matching_address_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task ADD matching_address_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE resolver_task DROP matching_address_ids');
        $this->addSql('COMMENT ON COLUMN resolver_task.matching_address_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE INDEX building_entrance_address_id_idx ON resolver_task (matching_address_id)');
    }
}
