<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200716105225 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cliente (id INT AUTO_INCREMENT NOT NULL, h_clinica VARCHAR(255) NOT NULL, nombre VARCHAR(255) NOT NULL, apellido VARCHAR(255) NOT NULL, dni INT NOT NULL, email VARCHAR(255) NOT NULL, telefono VARCHAR(255) NOT NULL, f_ingreso DATE DEFAULT NULL, f_egreso DATE DEFAULT NULL, motivo_ing INT DEFAULT NULL, motivo_egr INT DEFAULT NULL, activo TINYINT(1) NOT NULL, viene_de VARCHAR(255) DEFAULT NULL, doc_derivante VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE doctor_cliente (doctor_id INT NOT NULL, cliente_id INT NOT NULL, INDEX IDX_67B1F88887F4FB17 (doctor_id), INDEX IDX_67B1F888DE734E51 (cliente_id), PRIMARY KEY(doctor_id, cliente_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE doctor_cliente ADD CONSTRAINT FK_67B1F88887F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_cliente ADD CONSTRAINT FK_67B1F888DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE Person');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE doctor_cliente DROP FOREIGN KEY FK_67B1F888DE734E51');
        $this->addSql('CREATE TABLE Person (id INT NOT NULL, name VARCHAR(20) CHARACTER SET latin1 NOT NULL COLLATE `latin1_swedish_ci`) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE cliente');
        $this->addSql('DROP TABLE doctor_cliente');
    }
}
