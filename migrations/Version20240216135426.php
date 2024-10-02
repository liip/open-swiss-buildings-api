<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240216135426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'BuildingEntrance table: remove legacy_id, add imported_at timestamp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ADD imported_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE building_entrance DROP legacy_id');
        $this->addSql('COMMENT ON COLUMN building_entrance.imported_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX importedAt_idx ON building_entrance (imported_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX importedAt_idx');
        $this->addSql('ALTER TABLE building_entrance ADD legacy_id VARCHAR(10) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE building_entrance DROP imported_at');
    }
}
