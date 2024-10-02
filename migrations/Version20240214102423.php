<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240214102423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add resolver result table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE resolver_result (job_id UUID NOT NULL, building_id VARCHAR(255) DEFAULT NULL, building_entrance_id UUID NOT NULL, additional_data JSON NOT NULL, PRIMARY KEY(job_id, building_entrance_id))');
        $this->addSql('COMMENT ON COLUMN resolver_result.job_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.building_entrance_id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE resolver_result');
    }
}
