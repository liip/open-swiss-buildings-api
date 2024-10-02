<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240415132457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unify address columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX custom_building_entrance_idx');
        $this->addSql('DROP INDEX custom_building_entrance_abbreviation_idx');
        $this->addSql('DROP INDEX custom_building_entrance_house_number_idx');
        $this->addSql('DROP INDEX custom_building_entrance_house_number_abbreviation_idx');
        $this->addSql('DROP INDEX building_entrance_idx');
        $this->addSql('DROP INDEX building_entrance_abbreviation_idx');

        $this->addSql('ALTER TABLE building_entrance DROP entrance_number');
        $this->addSql('ALTER TABLE building_entrance DROP street_name_with_number');
        $this->addSql('ALTER TABLE building_entrance DROP street_name_abbreviation_with_number');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN street_name_abbreviation TO street_name_abbreviated');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN house_number TO street_house_number');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN house_number_suffix TO street_house_number_suffix');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN location_zip_code TO postal_code');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN location_name TO locality');

        $this->addSql('UPDATE building_entrance SET street_house_number = 0 WHERE street_house_number IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number SET NOT NULL');
        $this->addSql('UPDATE building_entrance SET street_house_number_suffix = \'\' WHERE street_house_number_suffix IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number_suffix SET NOT NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number_suffix TYPE VARCHAR(10)');

        $this->addSql('CREATE INDEX building_entrance_idx ON building_entrance (street_name, postal_code, locality)');
        $this->addSql('CREATE INDEX building_entrance_abbreviation_idx ON building_entrance (street_name_abbreviated, postal_code, locality)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_idx');
        $this->addSql('DROP INDEX building_entrance_abbreviation_idx');

        $this->addSql('ALTER TABLE building_entrance ADD entrance_number VARCHAR(12) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET entrance_number = CONCAT(street_house_number, street_house_number_suffix)');
        $this->addSql('ALTER TABLE building_entrance ALTER entrance_number SET NOT NULL');

        $this->addSql('ALTER TABLE building_entrance ADD street_name_with_number VARCHAR(72) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_name_with_number = CONCAT(street_name, \' \', street_house_number, street_house_number_suffix)');
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_with_number SET NOT NULL');

        $this->addSql('ALTER TABLE building_entrance ADD street_name_abbreviation_with_number VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_name_abbreviation_with_number = CONCAT(street_name_abbreviated, \' \', street_house_number, street_house_number_suffix)');
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_abbreviation_with_number SET NOT NULL');

        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number DROP NOT NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number_suffix DROP NOT NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number_suffix TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN street_name_abbreviated TO street_name_abbreviation');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN postal_code TO location_zip_code');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN locality TO location_name');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN street_house_number TO house_number');
        $this->addSql('ALTER TABLE building_entrance RENAME COLUMN street_house_number_suffix TO house_number_suffix');

        $this->addSql('CREATE INDEX custom_building_entrance_idx ON building_entrance (location_zip_code)');
        $this->addSql('CREATE INDEX custom_building_entrance_abbreviation_idx ON building_entrance (location_zip_code)');
        $this->addSql('CREATE INDEX custom_building_entrance_house_number_idx ON building_entrance (house_number, house_number_suffix, location_zip_code)');
        $this->addSql('CREATE INDEX custom_building_entrance_house_number_abbreviation_idx ON building_entrance (house_number, house_number_suffix, location_zip_code)');
        $this->addSql('CREATE INDEX building_entrance_idx ON building_entrance (street_name_with_number, location_zip_code, location_name)');
        $this->addSql('CREATE INDEX building_entrance_abbreviation_idx ON building_entrance (street_name_abbreviation_with_number, location_zip_code, location_name)');
    }
}
