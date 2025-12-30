<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251229144701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE report ADD review_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report ADD reported_club_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report ADD reported_book_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE report ALTER reported_id DROP NOT NULL');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F77843E2E969B FOREIGN KEY (review_id) REFERENCES review (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784C6908FC9 FOREIGN KEY (reported_club_id) REFERENCES club (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784B12B367A FOREIGN KEY (reported_book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C42F77843E2E969B ON report (review_id)');
        $this->addSql('CREATE INDEX IDX_C42F7784C6908FC9 ON report (reported_club_id)');
        $this->addSql('CREATE INDEX IDX_C42F7784B12B367A ON report (reported_book_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F77843E2E969B');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784C6908FC9');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784B12B367A');
        $this->addSql('DROP INDEX IDX_C42F77843E2E969B');
        $this->addSql('DROP INDEX IDX_C42F7784C6908FC9');
        $this->addSql('DROP INDEX IDX_C42F7784B12B367A');
        $this->addSql('ALTER TABLE report DROP review_id');
        $this->addSql('ALTER TABLE report DROP reported_club_id');
        $this->addSql('ALTER TABLE report DROP reported_book_id');
        $this->addSql('ALTER TABLE report ALTER reported_id SET NOT NULL');
    }
}
