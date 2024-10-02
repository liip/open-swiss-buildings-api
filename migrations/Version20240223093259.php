<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240223093259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Use foreign keys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result ADD CONSTRAINT FK_2A23065BE04EA9 FOREIGN KEY (job_id) REFERENCES resolver_job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resolver_result ADD CONSTRAINT FK_2A23065FB9BB54 FOREIGN KEY (building_entrance_id) REFERENCES building_entrance (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_2A23065FB9BB54 ON resolver_result (building_entrance_id)');
        $this->addSql('ALTER TABLE resolver_task ADD CONSTRAINT FK_A4748656BE04EA9 FOREIGN KEY (job_id) REFERENCES resolver_job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A4748656BE04EA9 ON resolver_task (job_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_task DROP CONSTRAINT FK_A4748656BE04EA9');
        $this->addSql('DROP INDEX IDX_A4748656BE04EA9');
        $this->addSql('ALTER TABLE resolver_result DROP CONSTRAINT FK_2A23065BE04EA9');
        $this->addSql('ALTER TABLE resolver_result DROP CONSTRAINT FK_2A23065FB9BB54');
        $this->addSql('DROP INDEX IDX_2A23065FB9BB54');
    }
}
