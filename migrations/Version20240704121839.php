<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240704121839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add support columns for Address-Search range matching';
    }

    public function up(Schema $schema): void
    {
        // See https://www.postgresql.org/docs/current/collation.html#ICU-COLLATION-LEVELS
        // We use the following collation settings:
        // kn=true : numbers within a string are treated as a single numeric value rather than a sequence of digits
        // ks=level2 → case insensitive comparisons
        $this->addSql("CREATE COLLATION custom_osb_numeric_ci (provider = icu, locale = 'en-u-kn-true-ks-level2', deterministic = false)");
        $this->addSql('ALTER TABLE resolver_address ADD range_from VARCHAR(4) DEFAULT NULL COLLATE custom_osb_numeric_ci');
        $this->addSql('ALTER TABLE resolver_address ADD range_to VARCHAR(4) DEFAULT NULL COLLATE custom_osb_numeric_ci');
        $this->addSql('ALTER TABLE resolver_address ADD range_type VARCHAR(10) DEFAULT NULL');
        $this->addSql('CREATE INDEX resolver_address_range_type_idx ON resolver_address (range_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX resolver_address_range_type_idx');
        $this->addSql('ALTER TABLE resolver_address DROP range_from');
        $this->addSql('ALTER TABLE resolver_address DROP range_to');
        $this->addSql('ALTER TABLE resolver_address DROP range_type');
        $this->addSql('DROP COLLATION custom_osb_numeric_ci');
    }
}
