<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240221084505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Separate ID for result table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result DROP CONSTRAINT resolver_result_pkey');
        $this->addSql('ALTER TABLE resolver_result ADD id UUID NOT NULL');
        $this->addSql('ALTER TABLE resolver_result ALTER building_entrance_id DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN resolver_result.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE INDEX resolver_result_job_id ON resolver_result (job_id)');
        $this->addSql('CREATE UNIQUE INDEX resolver_result_entry ON resolver_result (job_id, building_entrance_id)');
        $this->addSql('ALTER TABLE resolver_result ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX resolver_result_job_id');
        $this->addSql('DROP INDEX resolver_result_entry');
        $this->addSql('DROP INDEX resolver_result_pkey');
        $this->addSql('ALTER TABLE resolver_result DROP id');
        $this->addSql('ALTER TABLE resolver_result ALTER building_entrance_id SET NOT NULL');
        $this->addSql('ALTER TABLE resolver_result ADD PRIMARY KEY (job_id, building_entrance_id)');
    }
}
