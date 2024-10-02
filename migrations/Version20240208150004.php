<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240208150004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix transformed_wgs84_geometry type override, by using the Doctrine SQL comment feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN building_entrance.geo_coordinates_wgs84 IS \'(DC2Type:transformed_wgs84_geometry)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN building_entrance.geo_coordinates_wgs84 IS NULL');
    }
}
