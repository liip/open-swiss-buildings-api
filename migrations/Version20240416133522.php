<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240416133522 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make more space';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_abbreviated_normalized TYPE VARCHAR(32)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance ALTER street_name_abbreviated_normalized TYPE VARCHAR(24)');
    }
}
