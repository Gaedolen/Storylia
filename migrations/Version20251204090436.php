<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204090436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reading_month_book (id SERIAL NOT NULL, reading_month_id INT NOT NULL, book_id INT NOT NULL, utilisateur_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DC5593745CFBE054 ON reading_month_book (reading_month_id)');
        $this->addSql('CREATE INDEX IDX_DC55937416A2B381 ON reading_month_book (book_id)');
        $this->addSql('CREATE INDEX IDX_DC559374FB88E14F ON reading_month_book (utilisateur_id)');
        $this->addSql('ALTER TABLE reading_month_book ADD CONSTRAINT FK_DC5593745CFBE054 FOREIGN KEY (reading_month_id) REFERENCES club_reading_month (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reading_month_book ADD CONSTRAINT FK_DC55937416A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reading_month_book ADD CONSTRAINT FK_DC559374FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reading_month_book DROP CONSTRAINT FK_DC5593745CFBE054');
        $this->addSql('ALTER TABLE reading_month_book DROP CONSTRAINT FK_DC55937416A2B381');
        $this->addSql('ALTER TABLE reading_month_book DROP CONSTRAINT FK_DC559374FB88E14F');
        $this->addSql('DROP TABLE reading_month_book');
    }
}
