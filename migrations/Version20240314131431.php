<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240314131431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix municipality and canton';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE TABLE building_entrance CASCADE');
        $this->addSql('ALTER TABLE building_entrance ADD canton_code VARCHAR(2) NOT NULL');
        $this->addSql('ALTER TABLE building_entrance ALTER municipality TYPE VARCHAR(40)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE building_entrance DROP canton_code');
        $this->addSql('ALTER TABLE building_entrance ALTER municipality TYPE VARCHAR(2)');
    }
}
