<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105091832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE author ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE author DROP family_name');
        $this->addSql('ALTER TABLE author DROP first_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE author ADD family_name VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE author ADD first_name VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE author DROP name');
    }
}
