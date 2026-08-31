<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260830181031 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products ADD is_new_arrival BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE products ADD is_best_seller BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE products ADD tags JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD weight VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD material VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD colour VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD size VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE products ADD jewellery_type VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products DROP is_new_arrival');
        $this->addSql('ALTER TABLE products DROP is_best_seller');
        $this->addSql('ALTER TABLE products DROP tags');
        $this->addSql('ALTER TABLE products DROP weight');
        $this->addSql('ALTER TABLE products DROP material');
        $this->addSql('ALTER TABLE products DROP colour');
        $this->addSql('ALTER TABLE products DROP size');
        $this->addSql('ALTER TABLE products DROP jewellery_type');
    }
}
