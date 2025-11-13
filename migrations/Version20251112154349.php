<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112154349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_cbe5a331cc1cf4e6');
        $this->addSql('CREATE UNIQUE INDEX uniq_book_title_author_format ON book (title, author_id, format)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX uniq_book_title_author_format');
        $this->addSql('CREATE UNIQUE INDEX uniq_cbe5a331cc1cf4e6 ON book (isbn)');
    }
}
