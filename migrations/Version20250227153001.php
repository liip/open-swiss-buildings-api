<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250227153001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Handle country-code in building-entrance: definition setup (step 1/4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ADD country_code VARCHAR(2) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance DROP country_code');
    }
}
