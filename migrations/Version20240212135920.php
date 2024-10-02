<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240212135920 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add resolver task table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE resolver_task (id UUID NOT NULL, job_id UUID NOT NULL, matching_building_id VARCHAR(255) DEFAULT NULL, additional_data JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX task_matching_building_ids ON resolver_task (job_id, matching_building_id)');
        $this->addSql('COMMENT ON COLUMN resolver_task.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.job_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE resolver_task');
    }
}
