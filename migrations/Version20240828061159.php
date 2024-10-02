<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240828061159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index for GeoJSON resolving';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS geo_test');
        $this->addSql('DROP INDEX IF EXISTS geo_test2');
        $this->addSql('CREATE INDEX building_entrance_geo_coordinates_wgs84_idx_custom ON building_entrance USING GIST (geo_coordinates_wgs84)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_geo_coordinates_wgs84_idx_custom');
    }
}
