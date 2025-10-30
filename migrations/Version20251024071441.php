<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024071441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookshelf ADD reading_status_id INT NOT NULL');
        $this->addSql('ALTER TABLE bookshelf DROP reading_status');
        $this->addSql('ALTER TABLE bookshelf ADD CONSTRAINT FK_E1FF60F0A0C0446C FOREIGN KEY (reading_status_id) REFERENCES reading_status (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E1FF60F0A0C0446C ON bookshelf (reading_status_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE bookshelf DROP CONSTRAINT FK_E1FF60F0A0C0446C');
        $this->addSql('DROP INDEX IDX_E1FF60F0A0C0446C');
        $this->addSql('ALTER TABLE bookshelf ADD reading_status VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE bookshelf DROP reading_status_id');
    }
}
