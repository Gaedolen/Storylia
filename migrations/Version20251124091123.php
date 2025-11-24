<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124091123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reading_status ADD book_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reading_status ADD CONSTRAINT FK_E6BE237016A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_E6BE237016A2B381 ON reading_status (book_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reading_status DROP CONSTRAINT FK_E6BE237016A2B381');
        $this->addSql('DROP INDEX IDX_E6BE237016A2B381');
        $this->addSql('ALTER TABLE reading_status DROP book_id');
    }
}
