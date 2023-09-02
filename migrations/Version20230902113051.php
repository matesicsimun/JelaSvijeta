<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230902113051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dish ADD COLUMN date_modified DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__dish AS SELECT id, status_id, category_id, title_code, description_code FROM dish');
        $this->addSql('DROP TABLE dish');
        $this->addSql('CREATE TABLE dish (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, status_id INTEGER NOT NULL, category_id INTEGER DEFAULT NULL, title_code VARCHAR(255) NOT NULL, description_code VARCHAR(500) NOT NULL, CONSTRAINT FK_957D8CB86BF700BD FOREIGN KEY (status_id) REFERENCES status (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_957D8CB812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO dish (id, status_id, category_id, title_code, description_code) SELECT id, status_id, category_id, title_code, description_code FROM __temp__dish');
        $this->addSql('DROP TABLE __temp__dish');
        $this->addSql('CREATE INDEX IDX_957D8CB86BF700BD ON dish (status_id)');
        $this->addSql('CREATE INDEX IDX_957D8CB812469DE2 ON dish (category_id)');
    }
}
