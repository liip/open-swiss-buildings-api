<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250227153002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Handle country-code in building-entrance: migrating data (step 2/4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE building_entrance SET country_code = \'CH\' WHERE country_code IS NULL');
    }

    public function down(Schema $schema): void {}
}
