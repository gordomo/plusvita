<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200929184210 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, doctor_id INT NOT NULL, cliente_id INT NOT NULL, begin_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, INDEX IDX_E00CEDDEA76ED395 (user_id), INDEX IDX_E00CEDDE87F4FB17 (doctor_id), INDEX IDX_E00CEDDEDE734E51 (cliente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEDE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE booking');
    }
}
