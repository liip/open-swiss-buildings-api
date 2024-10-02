<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240325072758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove matching_address from task table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task DROP matching_address');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task ADD matching_address VARCHAR(255) DEFAULT NULL');
    }
}
