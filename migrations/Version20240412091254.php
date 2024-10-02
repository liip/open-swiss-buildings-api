<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240412091254 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Separate address table for address search resolving';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE resolver_address (id UUID NOT NULL, job_id UUID NOT NULL, unique_hash VARCHAR(255) NOT NULL, street VARCHAR(255) NOT NULL, street_name VARCHAR(255) NOT NULL, house_number INT DEFAULT NULL, house_number_suffix VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, additional_data JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B4397379BE04EA9 ON resolver_address (job_id)');
        $this->addSql('CREATE UNIQUE INDEX address_matching_uniqueness ON resolver_address (job_id, unique_hash)');
        $this->addSql('COMMENT ON COLUMN resolver_address.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address.job_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE resolver_address ADD CONSTRAINT FK_B4397379BE04EA9 FOREIGN KEY (job_id) REFERENCES resolver_job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resolver_task DROP ready');
        $this->addSql('ALTER TABLE resolver_task ADD matching_address_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN resolver_task.matching_address_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE building_entrance ADD house_number INT DEFAULT NULL');
        $this->addSql('ALTER TABLE building_entrance ADD house_number_suffix VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address DROP CONSTRAINT FK_B4397379BE04EA9');
        $this->addSql('DROP TABLE resolver_address');
        $this->addSql('ALTER TABLE resolver_task DROP matching_address_id');
        $this->addSql('ALTER TABLE resolver_task ADD ready BOOLEAN DEFAULT NULL');
        $this->addSql('UPDATE resolver_task SET ready = true WHERE ready IS NULL');
        $this->addSql('ALTER TABLE resolver_task ALTER ready SET NOT NULL');
        $this->addSql('ALTER TABLE building_entrance DROP house_number');
        $this->addSql('ALTER TABLE building_entrance DROP house_number_suffix');
    }
}
