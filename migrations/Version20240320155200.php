<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240320155200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OSBAPI-7: Municipality resolving';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task ADD matching_municipality_code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task DROP matching_municipality_code');
    }
}
