<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251026162924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_spent_amount ON spent (amount)');
        $this->addSql('CREATE INDEX idx_spent_description ON spent (description)');
        $this->addSql('CREATE INDEX idx_spent_category ON spent (category)');
        $this->addSql('CREATE INDEX idx_spent_date ON spent (date)');
        $this->addSql('CREATE INDEX idx_spent_month ON spent (month)');
        $this->addSql('CREATE INDEX idx_spent_year ON spent (year)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_spent_amount');
        $this->addSql('DROP INDEX idx_spent_description');
        $this->addSql('DROP INDEX idx_spent_category');
        $this->addSql('DROP INDEX idx_spent_date');
        $this->addSql('DROP INDEX idx_spent_month');
        $this->addSql('DROP INDEX idx_spent_year');
    }
}
