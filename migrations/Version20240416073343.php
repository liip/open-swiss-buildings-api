<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240416073343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add normalized address fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ADD street_name_normalized VARCHAR(60) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_name_normalized = street_name WHERE street_name_normalized IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_normalized SET NOT NULL');

        $this->addSql('ALTER TABLE building_entrance ADD locality_normalized VARCHAR(60) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET locality_normalized = locality WHERE locality_normalized IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER locality_normalized SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance DROP street_name_normalized');
        $this->addSql('ALTER TABLE building_entrance DROP locality_normalized');
    }
}
