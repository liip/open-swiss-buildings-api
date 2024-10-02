<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240429083724 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add match-type fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_address_match ADD match_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE resolver_address_match SET match_type =\'\' WHERE match_type IS NULL');
        $this->addSql('ALTER TABLE resolver_address_match ALTER match_type SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_address_street ADD match_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE resolver_address_street SET match_type =\'\' WHERE match_type IS NULL');
        $this->addSql('ALTER TABLE resolver_address_street ALTER match_type SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_result ADD match_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE resolver_result SET match_type =\'\' WHERE match_type IS NULL');
        $this->addSql('ALTER TABLE resolver_result ALTER match_type SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_task ADD match_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE resolver_task SET match_type =\'\' WHERE match_type IS NULL');
        $this->addSql('ALTER TABLE resolver_task ALTER match_type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result DROP match_type');
        $this->addSql('ALTER TABLE resolver_address_match DROP match_type');
        $this->addSql('ALTER TABLE resolver_address_street DROP match_type');
        $this->addSql('ALTER TABLE resolver_task DROP match_type');
    }
}
