<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251103135833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE utilisateur_club (utilisateur_id INT NOT NULL, club_id INT NOT NULL, PRIMARY KEY(utilisateur_id, club_id))');
        $this->addSql('CREATE INDEX IDX_716F5448FB88E14F ON utilisateur_club (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_716F544861190A32 ON utilisateur_club (club_id)');
        $this->addSql('ALTER TABLE utilisateur_club ADD CONSTRAINT FK_716F5448FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE utilisateur_club ADD CONSTRAINT FK_716F544861190A32 FOREIGN KEY (club_id) REFERENCES club (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE utilisateur_club DROP CONSTRAINT FK_716F5448FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_club DROP CONSTRAINT FK_716F544861190A32');
        $this->addSql('DROP TABLE utilisateur_club');
    }
}
