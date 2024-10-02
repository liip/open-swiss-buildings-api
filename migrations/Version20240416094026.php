<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240416094026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce resolver_address_match table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE resolver_address_match (id UUID NOT NULL, address_id UUID NOT NULL, confidence INT NOT NULL, matching_building_id VARCHAR(255) DEFAULT NULL, matching_entrance_id VARCHAR(255) DEFAULT NULL, additional_data JSON NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2D480E68F5B7AF75 ON resolver_address_match (address_id)');
        $this->addSql('CREATE INDEX resolver_address_match_idx ON resolver_address_match (matching_building_id, matching_entrance_id)');
        $this->addSql('COMMENT ON COLUMN resolver_address_match.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_match.address_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE resolver_address_match ADD CONSTRAINT FK_2D480E68F5B7AF75 FOREIGN KEY (address_id) REFERENCES resolver_address (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resolver_task DROP matching_address_ids');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address_match DROP CONSTRAINT FK_2D480E68F5B7AF75');
        $this->addSql('DROP TABLE resolver_address_match');
        $this->addSql('ALTER TABLE resolver_task ADD matching_address_ids JSON DEFAULT NULL');
    }
}
