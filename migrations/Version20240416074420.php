<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240416074420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add normalized abbreviated street name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ADD street_name_abbreviated_normalized VARCHAR(24) DEFAULT NULL');
        $this->addSql('UPDATE building_entrance SET street_name_abbreviated_normalized = street_name_abbreviated WHERE street_name_abbreviated_normalized IS NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_abbreviated_normalized SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance DROP street_name_abbreviated_normalized');
    }
}
