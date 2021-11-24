<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211116134916 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE examenes_complementarios_file (id INT AUTO_INCREMENT NOT NULL, path LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historia_habitaciones (id INT AUTO_INCREMENT NOT NULL, cliente_id INT NOT NULL, habitacion_id INT NOT NULL, n_cama VARCHAR(255) DEFAULT NULL, fecha DATE NOT NULL, INDEX IDX_5A3F4F8EDE734E51 (cliente_id), INDEX IDX_5A3F4F8EB009290D (habitacion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historia_habitaciones ADD CONSTRAINT FK_5A3F4F8EDE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE historia_habitaciones ADD CONSTRAINT FK_5A3F4F8EB009290D FOREIGN KEY (habitacion_id) REFERENCES habitacion (id)');
        $this->addSql('ALTER TABLE historia_ingreso CHANGE examenes_complementerios_files examenes_complementerios_files LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE examenes_complementarios_file');
        $this->addSql('DROP TABLE historia_habitaciones');
        $this->addSql('ALTER TABLE historia_ingreso CHANGE examenes_complementerios_files examenes_complementerios_files LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
