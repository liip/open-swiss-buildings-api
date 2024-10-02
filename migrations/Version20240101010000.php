<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240101010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialize PosGIS extension';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis;');
    }

    public function down(Schema $schema): void {}
}
