<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240423094019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add street_id field to building_entrance';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ADD street_id VARCHAR(8) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_id = \'\' WHERE street_id IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_id SET NOT NULL');

        $this->addSql('CREATE INDEX building_entrance_street_id_idx ON building_entrance (street_id, street_house_number, street_house_number_suffix)');
        $this->addSql('CREATE INDEX building_entrance_street_id_normalized_idx ON building_entrance (street_id, street_house_number, street_house_number_suffix_normalized)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_street_id_idx');
        $this->addSql('DROP INDEX building_entrance_street_id_normalized_idx');
        $this->addSql('ALTER TABLE building_entrance DROP street_id');
    }
}
