<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251218084453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajout de la colonne avec DEFAULT
        $this->addSql("ALTER TABLE utilisateur ADD is_verified BOOLEAN DEFAULT true");

        // 2. Sécurité : forcer true sur les lignes existantes
        $this->addSql("UPDATE utilisateur SET is_verified = true WHERE is_verified IS NULL");

        // 3. Maintenant seulement, NOT NULL
        $this->addSql("ALTER TABLE utilisateur ALTER COLUMN is_verified SET NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE utilisateur DROP is_verified');
    }
}
