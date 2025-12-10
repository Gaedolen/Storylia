<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208123153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT fk_5a10856416a2b381');
        $this->addSql('DROP INDEX idx_5a10856416a2b381');
        $this->addSql('ALTER TABLE vote RENAME COLUMN book_id TO book_proposal_id');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT FK_5A108564A68E6F24 FOREIGN KEY (book_proposal_id) REFERENCES book_proposal (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_5A108564A68E6F24 ON vote (book_proposal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE vote DROP CONSTRAINT FK_5A108564A68E6F24');
        $this->addSql('DROP INDEX IDX_5A108564A68E6F24');
        $this->addSql('ALTER TABLE vote RENAME COLUMN book_proposal_id TO book_id');
        $this->addSql('ALTER TABLE vote ADD CONSTRAINT fk_5a10856416a2b381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_5a10856416a2b381 ON vote (book_id)');
    }
}
