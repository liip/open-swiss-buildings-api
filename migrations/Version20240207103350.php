<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240207103350 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create BuildingEntrance table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE building_entrance (id UUID NOT NULL, legacy_id VARCHAR(10) NOT NULL, building_id VARCHAR(9) NOT NULL, entrance_id VARCHAR(2) NOT NULL, address_id VARCHAR(9) NOT NULL, entrance_number VARCHAR(12) NOT NULL, street_name VARCHAR(60) NOT NULL, street_name_abbreviation VARCHAR(24) NOT NULL, street_name_language VARCHAR(2) NOT NULL, location_zip_code VARCHAR(4) NOT NULL, location_name VARCHAR(60) NOT NULL, municipality VARCHAR(2) NOT NULL, municipality_code VARCHAR(4) NOT NULL, coordinates_lv95 JSON DEFAULT NULL, geo_coordinates_lv95 geometry(POINT, 2056) DEFAULT NULL, geo_coordinates_wgs84 geometry(POINT, 4326) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX buildingId_idx ON building_entrance (building_id)');
        $this->addSql('CREATE INDEX entranceId_idx ON building_entrance (entrance_id)');
        $this->addSql('CREATE UNIQUE INDEX building_entrance_language ON building_entrance (building_id, entrance_id, street_name_language)');
        $this->addSql('COMMENT ON COLUMN building_entrance.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE building_entrance');
    }
}
