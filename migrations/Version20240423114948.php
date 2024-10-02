<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240423114948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add street matching table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE resolver_address_street (address_id UUID NOT NULL, street_id VARCHAR(255) NOT NULL, confidence INT NOT NULL, PRIMARY KEY(address_id))');
        $this->addSql('CREATE INDEX resolver_address_street_idx ON resolver_address_street (address_id, street_id)');
        $this->addSql('COMMENT ON COLUMN resolver_address_street.address_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE resolver_address_street ADD CONSTRAINT FK_C3BADDF6F5B7AF75 FOREIGN KEY (address_id) REFERENCES resolver_address (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address_street DROP CONSTRAINT FK_C3BADDF6F5B7AF75');
        $this->addSql('DROP TABLE resolver_address_street');
    }
}
