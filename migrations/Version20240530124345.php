<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240530124345 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Resolving Address: allow multiple street-ids to match a given address';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address_street DROP CONSTRAINT resolver_address_street_pkey');
        $this->addSql('CREATE INDEX IDX_C3BADDF6F5B7AF75 ON resolver_address_street (address_id)');
        $this->addSql('ALTER TABLE resolver_address_street ADD PRIMARY KEY (address_id, street_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_C3BADDF6F5B7AF75');
        $this->addSql('DROP INDEX resolver_address_street_pkey');
        $this->addSql('ALTER TABLE resolver_address_street ADD PRIMARY KEY (address_id)');
    }
}
