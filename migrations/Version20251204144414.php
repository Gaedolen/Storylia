<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204144414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE book_proposal (id SERIAL NOT NULL, book_id INT NOT NULL, proposer_id INT NOT NULL, reading_month_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A61A822B16A2B381 ON book_proposal (book_id)');
        $this->addSql('CREATE INDEX IDX_A61A822BB13FA634 ON book_proposal (proposer_id)');
        $this->addSql('CREATE INDEX IDX_A61A822B5CFBE054 ON book_proposal (reading_month_id)');
        $this->addSql('COMMENT ON COLUMN book_proposal.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE book_proposal ADD CONSTRAINT FK_A61A822B16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE book_proposal ADD CONSTRAINT FK_A61A822BB13FA634 FOREIGN KEY (proposer_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE book_proposal ADD CONSTRAINT FK_A61A822B5CFBE054 FOREIGN KEY (reading_month_id) REFERENCES club_reading_month (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE book_proposal DROP CONSTRAINT FK_A61A822B16A2B381');
        $this->addSql('ALTER TABLE book_proposal DROP CONSTRAINT FK_A61A822BB13FA634');
        $this->addSql('ALTER TABLE book_proposal DROP CONSTRAINT FK_A61A822B5CFBE054');
        $this->addSql('DROP TABLE book_proposal');
    }
}
