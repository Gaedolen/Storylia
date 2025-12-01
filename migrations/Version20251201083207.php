<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201083207 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE club_message (id SERIAL NOT NULL, user_id INT NOT NULL, reading_month_id INT NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BBF595FDA76ED395 ON club_message (user_id)');
        $this->addSql('CREATE INDEX IDX_BBF595FD5CFBE054 ON club_message (reading_month_id)');
        $this->addSql('COMMENT ON COLUMN club_message.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE club_reading_month (id SERIAL NOT NULL, club_id INT NOT NULL, book_id INT NOT NULL, month VARCHAR(7) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5F40953761190A32 ON club_reading_month (club_id)');
        $this->addSql('CREATE INDEX IDX_5F40953716A2B381 ON club_reading_month (book_id)');
        $this->addSql('COMMENT ON COLUMN club_reading_month.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE club_review (id SERIAL NOT NULL, user_id INT NOT NULL, reading_month_id INT NOT NULL, comment TEXT NOT NULL, rating SMALLINT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_5C8C3D4CA76ED395 ON club_review (user_id)');
        $this->addSql('CREATE INDEX IDX_5C8C3D4C5CFBE054 ON club_review (reading_month_id)');
        $this->addSql('COMMENT ON COLUMN club_review.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE club_message ADD CONSTRAINT FK_BBF595FDA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_message ADD CONSTRAINT FK_BBF595FD5CFBE054 FOREIGN KEY (reading_month_id) REFERENCES club_reading_month (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_reading_month ADD CONSTRAINT FK_5F40953761190A32 FOREIGN KEY (club_id) REFERENCES club (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_reading_month ADD CONSTRAINT FK_5F40953716A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_review ADD CONSTRAINT FK_5C8C3D4CA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club_review ADD CONSTRAINT FK_5C8C3D4C5CFBE054 FOREIGN KEY (reading_month_id) REFERENCES club_reading_month (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE club_message DROP CONSTRAINT FK_BBF595FDA76ED395');
        $this->addSql('ALTER TABLE club_message DROP CONSTRAINT FK_BBF595FD5CFBE054');
        $this->addSql('ALTER TABLE club_reading_month DROP CONSTRAINT FK_5F40953761190A32');
        $this->addSql('ALTER TABLE club_reading_month DROP CONSTRAINT FK_5F40953716A2B381');
        $this->addSql('ALTER TABLE club_review DROP CONSTRAINT FK_5C8C3D4CA76ED395');
        $this->addSql('ALTER TABLE club_review DROP CONSTRAINT FK_5C8C3D4C5CFBE054');
        $this->addSql('DROP TABLE club_message');
        $this->addSql('DROP TABLE club_reading_month');
        $this->addSql('DROP TABLE club_review');
    }
}
