<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240228110728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resolver: add matching_geo_json column';
    }

    public function up(Schema $schema): void
    {
        // Delete all existing Resolver tables (Job, Task and Result)
        $this->addSql('TRUNCATE TABLE resolver_job CASCADE');
        $this->addSql('DROP INDEX task_matching_building_ids');
        $this->addSql('ALTER TABLE resolver_task ADD matching_unique_hash VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE resolver_task ADD matching_geo_json geometry(GEOMETRY, 4326) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN resolver_task.matching_geo_json IS \'(DC2Type:geojson)\'');
        $this->addSql('CREATE UNIQUE INDEX task_matching_uniqueness ON resolver_task (job_id, matching_unique_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX task_matching_uniqueness');
        $this->addSql('ALTER TABLE resolver_task DROP matching_unique_hash');
        $this->addSql('ALTER TABLE resolver_task DROP matching_geo_json');
        $this->addSql('CREATE UNIQUE INDEX task_matching_building_ids ON resolver_task (job_id, matching_building_id)');
    }
}
