<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213145321 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE review DROP CONSTRAINT fk_794381c65cfbe054');
        $this->addSql('DROP INDEX idx_794381c65cfbe054');
        $this->addSql('ALTER TABLE review DROP reading_month_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE review ADD reading_month_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT fk_794381c65cfbe054 FOREIGN KEY (reading_month_id) REFERENCES club_reading_month (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_794381c65cfbe054 ON review (reading_month_id)');
    }
}
