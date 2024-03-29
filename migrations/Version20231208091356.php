<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231208091356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookmark ADD lastupdate DATETIME NOT NULL DEFAULT "2023-12-08 09:13:24"');
        // $date = new \DateTime('now', new \DateTimeZone(date_default_timezone_get()));
        // $this->addSql('UPDATE bookmark SET lastupdate = "'. $date->format("Y-m-d H:i:s") .'"');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bookmark DROP lastupdate');
    }
}
