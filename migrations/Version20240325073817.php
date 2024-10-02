<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240325073817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove original address from result table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result DROP address');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result ADD address VARCHAR(255) DEFAULT NULL');
    }
}
