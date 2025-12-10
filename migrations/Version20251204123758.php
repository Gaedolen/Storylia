<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204123758 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vote (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, club_reading_month_id INT NOT NULL, book_id INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5A108564FB88E14F ON vote (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_5A1085643D8F09B5 ON vote (club_reading_month_id)');
        $this->addSql('CREATE INDEX IDX_5A10856416A2B381 ON vote (book_id)');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A1085643D8F09B5 FOREIGN KEY (club_reading_month_id) REFERENCES club_reading_month (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A10856416A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT FK_5A108564FB88E14F');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT FK_5A1085643D8F09B5');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT FK_5A10856416A2B381');
        $this->addSql('DROP TABLE vote');
    }
}
