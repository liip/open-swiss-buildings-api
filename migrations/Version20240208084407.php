<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240208084407 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Resolving tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE resolver_job (id UUID NOT NULL, type VARCHAR(255) NOT NULL, data BYTEA NOT NULL, metadata JSON NOT NULL, state VARCHAR(255) NOT NULL, failure JSON NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, modified_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN resolver_job.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.modified_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.expires_at IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE resolver_job');
    }
}
