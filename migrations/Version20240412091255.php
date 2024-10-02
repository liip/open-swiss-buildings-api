<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240412091255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for address search resolving';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX building_entrance_address_id_idx ON resolver_task (matching_address_id)');
        $this->addSql('CREATE INDEX building_entrance_building_id_idx ON resolver_task (matching_building_id)');
        $this->addSql('CREATE INDEX building_entrance_municipality_code_idx ON resolver_task (matching_municipality_code)');

        $this->addSql('CREATE INDEX CUSTOM_building_entrance_idx ON building_entrance (lower(street_name_with_number), location_zip_code, lower(location_name))');
        $this->addSql('CREATE INDEX CUSTOM_building_entrance_abbreviation_idx ON building_entrance (lower(street_name_abbreviation_with_number), location_zip_code, lower(location_name))');

        $this->addSql('CREATE INDEX CUSTOM_building_entrance_house_number_idx ON building_entrance (lower(street_name), house_number, house_number_suffix, location_zip_code, lower(location_name))');
        $this->addSql('CREATE INDEX CUSTOM_building_entrance_house_number_abbreviation_idx ON building_entrance (lower(street_name_abbreviation), house_number, house_number_suffix, location_zip_code, lower(location_name))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_address_id_idx');
        $this->addSql('DROP INDEX building_entrance_building_id_idx');
        $this->addSql('DROP INDEX building_entrance_municipality_code_idx');

        $this->addSql('DROP INDEX CUSTOM_building_entrance_idx');
        $this->addSql('DROP INDEX CUSTOM_building_entrance_abbreviation_idx');
        $this->addSql('DROP INDEX CUSTOM_building_entrance_house_number_idx');
        $this->addSql('DROP INDEX CUSTOM_building_entrance_house_number_abbreviation_idx');
    }
}
