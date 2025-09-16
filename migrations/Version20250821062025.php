<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250821062025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust schema with Doctrine ORM 3 changes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN building_entrance.id IS \'\'');
        $this->addSql('COMMENT ON COLUMN building_entrance.geo_coordinates_wgs84 IS \'\'');
        $this->addSql('COMMENT ON COLUMN building_entrance.imported_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_address.id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_address.job_id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_match.id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_match.address_id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_street.address_id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.created_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.modified_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.expires_at IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.job_id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.building_entrance_id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.job_id IS \'\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.matching_geo_json IS \'\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('COMMENT ON COLUMN building_entrance.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN building_entrance.geo_coordinates_wgs84 IS \'(DC2Type:transformed_wgs84_geometry)\'');
        $this->addSql('COMMENT ON COLUMN building_entrance.imported_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address.job_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_match.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_match.address_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_address_street.address_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.modified_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resolver_job.expires_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.job_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_result.building_entrance_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.matching_geo_json IS \'(DC2Type:geojson)\'');
        $this->addSql('COMMENT ON COLUMN resolver_task.job_id IS \'(DC2Type:uuid)\'');
    }
}
