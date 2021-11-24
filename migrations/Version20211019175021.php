<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211019175021 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE notas_historia_clinica (id INT AUTO_INCREMENT NOT NULL, user_id_id INT NOT NULL, client_id_id INT NOT NULL, text VARCHAR(1024) NOT NULL, fecha DATE NOT NULL, INDEX IDX_A1A5A1F49D86650F (user_id_id), INDEX IDX_A1A5A1F4DC2902E0 (client_id_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE notas_historia_clinica ADD CONSTRAINT FK_A1A5A1F49D86650F FOREIGN KEY (user_id_id) REFERENCES doctor (id)');
        $this->addSql('ALTER TABLE notas_historia_clinica ADD CONSTRAINT FK_A1A5A1F4DC2902E0 FOREIGN KEY (client_id_id) REFERENCES cliente (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE notas_historia_clinica');
    }
}
