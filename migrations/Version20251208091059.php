<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208091059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE club_reading_month_proposals DROP CONSTRAINT fk_1e0b5e1116a2b381');
        $this->addSql('ALTER TABLE club_reading_month_proposals DROP CONSTRAINT fk_1e0b5e113d8f09b5');
        $this->addSql('DROP TABLE club_reading_month_proposals');
        $this->addSql('ALTER TABLE club_reading_month ALTER book_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE club_reading_month_proposals (club_reading_month_id INT NOT NULL, book_id INT NOT NULL, PRIMARY KEY(club_reading_month_id, book_id))');
        $this->addSql('CREATE INDEX idx_1e0b5e1116a2b381 ON club_reading_month_proposals (book_id)');
        $this->addSql('CREATE INDEX idx_1e0b5e113d8f09b5 ON club_reading_month_proposals (club_reading_month_id)');
        $this->addSql('ALTER TABLE club_reading_month_proposals ADD CONSTRAINT fk_1e0b5e1116a2b381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_reading_month_proposals ADD CONSTRAINT fk_1e0b5e113d8f09b5 FOREIGN KEY (club_reading_month_id) REFERENCES club_reading_month (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_reading_month ALTER book_id SET NOT NULL');
    }
}
