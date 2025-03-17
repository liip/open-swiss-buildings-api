<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250303160002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Handle country-code in resolving: define uniqueness with country-code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result ADD country_code VARCHAR(2) DEFAULT NULL');
        $this->addSql('DROP INDEX resolver_result_entry');
        $this->addSql('CREATE UNIQUE INDEX resolver_result_entry ON resolver_result (job_id, country_code, building_entrance_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX resolver_result_entry');
        $this->addSql('ALTER TABLE resolver_result DROP country_code');
        $this->addSql('CREATE UNIQUE INDEX resolver_result_entry ON resolver_result (job_id, building_entrance_id)');
    }
}
