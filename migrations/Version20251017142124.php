<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017142124 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE author (id SERIAL NOT NULL, family_name VARCHAR(50) NOT NULL, first_name VARCHAR(50) DEFAULT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE book (id SERIAL NOT NULL, author_id INT NOT NULL, title VARCHAR(50) NOT NULL, summary TEXT NOT NULL, publication_date DATE NOT NULL, genre VARCHAR(50) NOT NULL, theme VARCHAR(50) NOT NULL, edition VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CBE5A331F675F31B ON book (author_id)');
        $this->addSql('CREATE TABLE bookshelf (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, book_id INT NOT NULL, reading_status VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E1FF60F0FB88E14F ON bookshelf (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_E1FF60F016A2B381 ON bookshelf (book_id)');
        $this->addSql('CREATE TABLE club (id SERIAL NOT NULL, creator_id INT NOT NULL, name VARCHAR(50) NOT NULL, description TEXT NOT NULL, creation_date DATE NOT NULL, status VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B8EE387261220EA6 ON club (creator_id)');
        $this->addSql('CREATE TABLE reading_history (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, book_id INT NOT NULL, reading_date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6323E37EFB88E14F ON reading_history (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_6323E37E16A2B381 ON reading_history (book_id)');
        $this->addSql('CREATE TABLE reading_status (id SERIAL NOT NULL, label VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE report (id SERIAL NOT NULL, author_id INT NOT NULL, reported_id INT NOT NULL, date DATE NOT NULL, message TEXT NOT NULL, status VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C42F7784F675F31B ON report (author_id)');
        $this->addSql('CREATE INDEX IDX_C42F778494BDEEB6 ON report (reported_id)');
        $this->addSql('CREATE TABLE review (id SERIAL NOT NULL, utilisateur_id INT NOT NULL, book_id INT NOT NULL, author_id INT NOT NULL, comment TEXT NOT NULL, rating SMALLINT NOT NULL, date DATE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_794381C6FB88E14F ON review (utilisateur_id)');
        $this->addSql('CREATE INDEX IDX_794381C616A2B381 ON review (book_id)');
        $this->addSql('CREATE INDEX IDX_794381C6F675F31B ON review (author_id)');
        $this->addSql('CREATE TABLE role (id SERIAL NOT NULL, label VARCHAR(50) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE utilisateur (id SERIAL NOT NULL, role_id INT DEFAULT NULL, family_name VARCHAR(50) NOT NULL, first_name VARCHAR(50) NOT NULL, pseudo VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, birth_date DATE NOT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1D1C63B3D60322AC ON utilisateur (role_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE book ADD CONSTRAINT FK_CBE5A331F675F31B FOREIGN KEY (author_id) REFERENCES author (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bookshelf ADD CONSTRAINT FK_E1FF60F0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE bookshelf ADD CONSTRAINT FK_E1FF60F016A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE club ADD CONSTRAINT FK_B8EE387261220EA6 FOREIGN KEY (creator_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reading_history ADD CONSTRAINT FK_6323E37EFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reading_history ADD CONSTRAINT FK_6323E37E16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F7784F675F31B FOREIGN KEY (author_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE report ADD CONSTRAINT FK_C42F778494BDEEB6 FOREIGN KEY (reported_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C616A2B381 FOREIGN KEY (book_id) REFERENCES book (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C6F675F31B FOREIGN KEY (author_id) REFERENCES author (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B3D60322AC FOREIGN KEY (role_id) REFERENCES role (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE book DROP CONSTRAINT FK_CBE5A331F675F31B');
        $this->addSql('ALTER TABLE bookshelf DROP CONSTRAINT FK_E1FF60F0FB88E14F');
        $this->addSql('ALTER TABLE bookshelf DROP CONSTRAINT FK_E1FF60F016A2B381');
        $this->addSql('ALTER TABLE club DROP CONSTRAINT FK_B8EE387261220EA6');
        $this->addSql('ALTER TABLE reading_history DROP CONSTRAINT FK_6323E37EFB88E14F');
        $this->addSql('ALTER TABLE reading_history DROP CONSTRAINT FK_6323E37E16A2B381');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F7784F675F31B');
        $this->addSql('ALTER TABLE report DROP CONSTRAINT FK_C42F778494BDEEB6');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C6FB88E14F');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C616A2B381');
        $this->addSql('ALTER TABLE review DROP CONSTRAINT FK_794381C6F675F31B');
        $this->addSql('ALTER TABLE utilisateur DROP CONSTRAINT FK_1D1C63B3D60322AC');
        $this->addSql('DROP TABLE author');
        $this->addSql('DROP TABLE book');
        $this->addSql('DROP TABLE bookshelf');
        $this->addSql('DROP TABLE club');
        $this->addSql('DROP TABLE reading_history');
        $this->addSql('DROP TABLE reading_status');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
