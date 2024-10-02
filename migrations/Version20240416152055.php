<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240416152055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize house number suffix';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ADD street_house_number_suffix_normalized VARCHAR(14) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_house_number_suffix_normalized = street_house_number_suffix WHERE street_house_number_suffix_normalized IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_house_number_suffix_normalized SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address ADD street_house_number_suffix_normalized VARCHAR(14) DEFAULT NULL');
        $this->addSql('UPDATE resolver_address SET street_house_number_suffix_normalized = street_house_number_suffix WHERE street_house_number_suffix_normalized IS NULL');
        $this->addSql('ALTER TABLE resolver_address ALTER street_house_number_suffix_normalized SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance DROP street_house_number_suffix_normalized');
        $this->addSql('ALTER TABLE resolver_address DROP street_house_number_suffix_normalized');
    }
}
