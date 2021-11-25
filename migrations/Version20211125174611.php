<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211125174611 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE evolucion (id INT AUTO_INCREMENT NOT NULL, paciente_id INT NOT NULL, user_id INT NOT NULL, tipo VARCHAR(255) NOT NULL, fecha DATETIME NOT NULL, description LONGTEXT NOT NULL, adjunto_url LONGTEXT DEFAULT NULL, INDEX IDX_8FC247B57310DAD4 (paciente_id), INDEX IDX_8FC247B5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE evolucion ADD CONSTRAINT FK_8FC247B57310DAD4 FOREIGN KEY (paciente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE evolucion ADD CONSTRAINT FK_8FC247B5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE evolucion');
    }
}
