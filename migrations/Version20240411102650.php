<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240411102650 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'BuildingEntrance: add index on canton-code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX cantonCode_idx ON building_entrance (canton_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX cantonCode_idx');
    }
}
