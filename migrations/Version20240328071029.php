<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240328071029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add confidence as column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result ADD confidence INT DEFAULT NULL');
        $this->addSql('UPDATE resolver_result SET confidence = 0 WHERE confidence IS NULL');
        $this->addSql('ALTER TABLE resolver_result ALTER confidence SET NOT NULL');

        $this->addSql('ALTER TABLE resolver_task ADD confidence INT DEFAULT NULL');
        $this->addSql('UPDATE resolver_task SET confidence = 0 WHERE confidence IS NULL');
        $this->addSql('ALTER TABLE resolver_task ALTER confidence SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE resolver_result DROP confidence');
        $this->addSql('ALTER TABLE resolver_task DROP confidence');
    }
}
