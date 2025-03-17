<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250227153004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Handle country-code in building-entrance: define uniqueness with country-code (4/4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_language');
        $this->addSql('CREATE UNIQUE INDEX building_entrance_language ON building_entrance (country_code, building_id, entrance_id, street_name_language)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX building_entrance_language');
        $this->addSql('CREATE UNIQUE INDEX building_entrance_language ON building_entrance (building_id, entrance_id, street_name_language)');
    }
}
