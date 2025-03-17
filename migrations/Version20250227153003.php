<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250227153003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Handle country-code in building-entrance: finalizing definition (step 3/4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ALTER COLUMN country_code SET NOT NULL');
        $this->addSql('CREATE INDEX countryCode_idx ON building_entrance (country_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX countryCode_idx');
        $this->addSql('ALTER TABLE building_entrance ALTER COLUMN country_code SET NULL');
    }
}
