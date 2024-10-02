<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240402070129 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add columns to improve resolving';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task ADD ready BOOLEAN DEFAULT NULL');
        $this->addSql('UPDATE resolver_task SET ready = true WHERE ready IS NULL');
        $this->addSql('ALTER TABLE resolver_task ALTER ready SET NOT NULL');

        $this->addSql('ALTER TABLE building_entrance ADD street_name_with_number VARCHAR(72) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_name_with_number = CONCAT(street_name, \' \', entrance_number) WHERE street_name_with_number IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_with_number SET NOT NULL');

        $this->addSql('ALTER TABLE building_entrance ADD street_name_abbreviation_with_number VARCHAR(36) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_name_abbreviation_with_number = CONCAT(street_name_abbreviation, \' \', entrance_number) WHERE street_name_abbreviation_with_number IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_abbreviation_with_number SET NOT NULL');

        $this->addSql('CREATE INDEX building_entrance_idx ON building_entrance (street_name_with_number, location_zip_code, location_name)');
        $this->addSql('CREATE INDEX building_entrance_abbreviation_idx ON building_entrance (street_name_abbreviation_with_number, location_zip_code, location_name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_idx');
        $this->addSql('DROP INDEX building_entrance_abbreviation_idx');

        $this->addSql('ALTER TABLE resolver_task DROP ready');
        $this->addSql('ALTER TABLE building_entrance DROP street_name_with_number');
        $this->addSql('ALTER TABLE building_entrance DROP street_name_abbreviation_with_number');
    }
}
