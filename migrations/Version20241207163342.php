<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241207163342 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE month (id SERIAL NOT NULL, number INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE month_advice (month_id INT NOT NULL, advice_id INT NOT NULL, PRIMARY KEY(month_id, advice_id))');
        $this->addSql('CREATE INDEX IDX_6FB6E213A0CBDE4 ON month_advice (month_id)');
        $this->addSql('CREATE INDEX IDX_6FB6E21312998205 ON month_advice (advice_id)');
        $this->addSql('ALTER TABLE month_advice ADD CONSTRAINT FK_6FB6E213A0CBDE4 FOREIGN KEY (month_id) REFERENCES month (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE month_advice ADD CONSTRAINT FK_6FB6E21312998205 FOREIGN KEY (advice_id) REFERENCES advice (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE advice DROP month');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE month_advice DROP CONSTRAINT FK_6FB6E213A0CBDE4');
        $this->addSql('ALTER TABLE month_advice DROP CONSTRAINT FK_6FB6E21312998205');
        $this->addSql('DROP TABLE month');
        $this->addSql('DROP TABLE month_advice');
        $this->addSql('ALTER TABLE advice ADD month VARCHAR(255) NOT NULL');
    }
}
