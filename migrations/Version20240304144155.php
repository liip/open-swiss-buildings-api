<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240304144155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'OSBAPI-6: Prepare for address search resolving';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task ADD matching_entrance_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE resolver_task ADD matching_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE resolver_result ADD entrance_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE resolver_result ADD address VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task DROP matching_entrance_id');
        $this->addSql('ALTER TABLE resolver_task DROP matching_address');
        $this->addSql('ALTER TABLE resolver_result DROP entrance_id');
        $this->addSql('ALTER TABLE resolver_result DROP address');
    }
}
