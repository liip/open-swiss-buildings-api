<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240416080639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Address fields on resolver_address';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address RENAME COLUMN location TO locality');
        $this->addSql('ALTER TABLE resolver_address ALTER locality TYPE VARCHAR(60)');

        $this->addSql('ALTER TABLE resolver_address RENAME COLUMN house_number TO street_house_number');
        $this->addSql('UPDATE resolver_address SET street_house_number = 0 WHERE street_house_number IS NULL');
        $this->addSql('ALTER TABLE resolver_address ALTER street_house_number SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address RENAME COLUMN house_number_suffix TO street_house_number_suffix');
        $this->addSql('ALTER TABLE resolver_address ALTER street_house_number_suffix TYPE VARCHAR(10)');
        $this->addSql('UPDATE resolver_address SET street_house_number_suffix = \'\' WHERE street_house_number_suffix IS NULL');
        $this->addSql('ALTER TABLE resolver_address ALTER street_house_number_suffix SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address ADD street_name_normalized VARCHAR(60) DEFAULT NULL');
        $this->addSql('UPDATE resolver_address SET street_name_normalized = street_name WHERE street_name_normalized IS NULL');
        $this->addSql('ALTER TABLE resolver_address ALTER street_name_normalized SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address ADD locality_normalized VARCHAR(60) DEFAULT NULL');
        $this->addSql('UPDATE resolver_address SET locality_normalized = locality WHERE locality_normalized IS NULL');
        $this->addSql('ALTER TABLE resolver_address ALTER locality_normalized SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address DROP street');

        $this->addSql('ALTER TABLE resolver_address ALTER street_name TYPE VARCHAR(60)');
        $this->addSql('ALTER TABLE resolver_address ALTER postal_code TYPE VARCHAR(4)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address RENAME COLUMN locality TO location');
        $this->addSql('ALTER TABLE resolver_address ALTER location TYPE VARCHAR(255)');

        $this->addSql('ALTER TABLE resolver_address RENAME COLUMN street_house_number TO house_number');
        $this->addSql('ALTER TABLE resolver_address ALTER house_number DROP NOT NULL');
        $this->addSql('UPDATE resolver_address SET house_number = NULL WHERE street_house_number = 0');

        $this->addSql('ALTER TABLE resolver_address RENAME COLUMN street_house_number_suffix TO house_number_suffix');
        $this->addSql('ALTER TABLE resolver_address ALTER house_number_suffix TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE resolver_address ALTER house_number_suffix DROP NOT NULL');
        $this->addSql('UPDATE resolver_address SET house_number_suffix = NULL WHERE house_number_suffix = \'\'');

        $this->addSql('ALTER TABLE resolver_address ADD street VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE resolver_address SET street = CONCAT(street_name, \' \', house_number, house_number_suffix) WHERE street IS NULL');
        $this->addSql('ALTER TABLE resolver_address ALTER street SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address DROP street_name_normalized');
        $this->addSql('ALTER TABLE resolver_address DROP locality_normalized');

        $this->addSql('ALTER TABLE resolver_address ALTER street_name TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE resolver_address ALTER postal_code TYPE VARCHAR(255)');
    }
}
