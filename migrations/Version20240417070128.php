<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240417070128 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust address indexes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_idx');
        $this->addSql('DROP INDEX building_entrance_abbreviation_idx');
        $this->addSql('CREATE INDEX building_entrance_normalized_idx ON building_entrance (street_name_normalized, street_house_number, street_house_number_suffix_normalized, postal_code, locality_normalized)');
        $this->addSql('CREATE INDEX building_entrance_abbreviation_normalized_idx ON building_entrance (street_name_abbreviated_normalized, street_house_number, street_house_number_suffix_normalized, postal_code, locality_normalized)');
        $this->addSql('CREATE INDEX building_entrance_idx ON building_entrance (street_name, street_house_number, street_house_number_suffix, postal_code, locality)');
        $this->addSql('CREATE INDEX building_entrance_abbreviation_idx ON building_entrance (street_name_abbreviated, street_house_number, street_house_number_suffix, postal_code, locality)');
        $this->addSql('DROP INDEX resolver_address_idx');
        $this->addSql('CREATE INDEX resolver_address_normalized_idx ON resolver_address (street_name_normalized, street_house_number, street_house_number_suffix_normalized, postal_code, locality_normalized)');
        $this->addSql('CREATE INDEX resolver_address_idx ON resolver_address (street_name, street_house_number, street_house_number_suffix, postal_code, locality)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_normalized_idx');
        $this->addSql('DROP INDEX building_entrance_abbreviation_normalized_idx');
        $this->addSql('DROP INDEX building_entrance_idx');
        $this->addSql('DROP INDEX building_entrance_abbreviation_idx');
        $this->addSql('CREATE INDEX building_entrance_idx ON building_entrance (street_name_normalized, street_house_number, street_house_number_suffix, postal_code, locality)');
        $this->addSql('CREATE INDEX building_entrance_abbreviation_idx ON building_entrance (street_name_abbreviated_normalized, street_house_number, street_house_number_suffix, postal_code, locality)');
        $this->addSql('DROP INDEX resolver_address_normalized_idx');
        $this->addSql('DROP INDEX resolver_address_idx');
        $this->addSql('CREATE INDEX resolver_address_idx ON resolver_address (street_name_normalized, street_house_number, street_house_number_suffix, postal_code, locality)');
    }
}
