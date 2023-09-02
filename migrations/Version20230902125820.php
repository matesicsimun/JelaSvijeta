<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230902125820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE translation ADD COLUMN short_code VARCHAR(2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__translation AS SELECT id, language_code, code, translation FROM translation');
        $this->addSql('DROP TABLE translation');
        $this->addSql('CREATE TABLE translation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, language_code VARCHAR(5) NOT NULL, code VARCHAR(255) NOT NULL, translation VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO translation (id, language_code, code, translation) SELECT id, language_code, code, translation FROM __temp__translation');
        $this->addSql('DROP TABLE __temp__translation');
    }
}
