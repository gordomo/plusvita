<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211104015418 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE historico_habitaciones (id INT AUTO_INCREMENT NOT NULL, cliente_id INT NOT NULL, habitacion_id INT NOT NULL, fecha DATE NOT NULL, cama VARCHAR(2) NOT NULL, UNIQUE INDEX UNIQ_EC448783DE734E51 (cliente_id), UNIQUE INDEX UNIQ_EC448783B009290D (habitacion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historico_habitaciones ADD CONSTRAINT FK_EC448783DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE historico_habitaciones ADD CONSTRAINT FK_EC448783B009290D FOREIGN KEY (habitacion_id) REFERENCES habitacion (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE historico_habitaciones');
    }
}
