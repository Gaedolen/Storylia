<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251110105340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE book ADD vo_title VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD genres JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD subjects JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD isbn VARCHAR(13) NOT NULL');
        $this->addSql('ALTER TABLE book ADD pages INT DEFAULT NULL');
        $this->addSql('ALTER TABLE book ADD publishers JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE book DROP genre');
        $this->addSql('ALTER TABLE book DROP edition');
        $this->addSql('ALTER TABLE book ALTER summary DROP NOT NULL');
        $this->addSql('ALTER TABLE book RENAME COLUMN theme TO format');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CBE5A331CC1CF4E6 ON book (isbn)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_CBE5A331CC1CF4E6');
        $this->addSql('ALTER TABLE book ADD genre VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE book ADD edition VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE book DROP vo_title');
        $this->addSql('ALTER TABLE book DROP genres');
        $this->addSql('ALTER TABLE book DROP subjects');
        $this->addSql('ALTER TABLE book DROP isbn');
        $this->addSql('ALTER TABLE book DROP pages');
        $this->addSql('ALTER TABLE book DROP publishers');
        $this->addSql('ALTER TABLE book ALTER summary SET NOT NULL');
        $this->addSql('ALTER TABLE book RENAME COLUMN format TO theme');
    }
}
