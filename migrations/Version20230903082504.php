<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230903082504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE language ADD COLUMN short_code VARCHAR(2) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__language AS SELECT id, code FROM language');
        $this->addSql('DROP TABLE language');
        $this->addSql('CREATE TABLE language (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(5) NOT NULL)');
        $this->addSql('INSERT INTO language (id, code) SELECT id, code FROM __temp__language');
        $this->addSql('DROP TABLE __temp__language');
    }
}
