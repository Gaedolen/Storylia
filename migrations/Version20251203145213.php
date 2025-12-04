<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203145213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_reading_month_proposals (club_reading_month_id INT NOT NULL, book_id INT NOT NULL, PRIMARY KEY(club_reading_month_id, book_id))');
        $this->addSql('CREATE INDEX IDX_1E0B5E113D8F09B5 ON club_reading_month_proposals (club_reading_month_id)');
        $this->addSql('CREATE INDEX IDX_1E0B5E1116A2B381 ON club_reading_month_proposals (book_id)');
        $this->addSql('ALTER TABLE club_reading_month_proposals ADD CONSTRAINT FK_1E0B5E113D8F09B5 FOREIGN KEY (club_reading_month_id) REFERENCES club_reading_month (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_reading_month_proposals ADD CONSTRAINT FK_1E0B5E1116A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE club_reading_month_proposals DROP CONSTRAINT FK_1E0B5E113D8F09B5');
        $this->addSql('ALTER TABLE club_reading_month_proposals DROP CONSTRAINT FK_1E0B5E1116A2B381');
        $this->addSql('DROP TABLE club_reading_month_proposals');
    }
}
