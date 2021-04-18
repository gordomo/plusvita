<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210417183355 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE adjuntos_pacientes (id INT AUTO_INCREMENT NOT NULL, id_paciente VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, nombre VARCHAR(255) DEFAULT NULL, tipo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, telefono VARCHAR(255) DEFAULT NULL, legajo VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, doctor_id INT NOT NULL, cliente_id INT NOT NULL, begin_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, title VARCHAR(255) NOT NULL, dias JSON DEFAULT NULL, desde DATE DEFAULT NULL, hasta DATE DEFAULT NULL, completado TINYINT(1) DEFAULT NULL, INDEX IDX_E00CEDDEA76ED395 (user_id), INDEX IDX_E00CEDDE87F4FB17 (doctor_id), INDEX IDX_E00CEDDEDE734E51 (cliente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consumibles_clientes (id INT AUTO_INCREMENT NOT NULL, consumible_id INT NOT NULL, cliente_id INT NOT NULL, fecha DATE NOT NULL, cantidad INT NOT NULL, accion INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE adjuntos_staff (id INT AUTO_INCREMENT NOT NULL, id_staff VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, tipo VARCHAR(255) NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historia_paciente (id INT AUTO_INCREMENT NOT NULL, cliente_id INT DEFAULT NULL, modalidad VARCHAR(255) DEFAULT NULL, patologia VARCHAR(255) DEFAULT NULL, patologia_especifica VARCHAR(255) DEFAULT NULL, obra_social VARCHAR(255) DEFAULT NULL, n_afiliado_obra_social VARCHAR(255) DEFAULT NULL, sistema_de_emergencia VARCHAR(255) DEFAULT NULL, n_afiliado_sistema_de_emergencia VARCHAR(255) DEFAULT NULL, habitacion VARCHAR(255) DEFAULT NULL, cama VARCHAR(255) DEFAULT NULL, id_paciente INT NOT NULL, fecha DATE NOT NULL, fecha_ingreso DATE DEFAULT NULL, fecha_engreso DATE DEFAULT NULL, usuario VARCHAR(255) NOT NULL, fecha_derivacion DATETIME DEFAULT NULL, fecha_reingreso_derivacion DATETIME DEFAULT NULL, motivo_derivacion VARCHAR(255) DEFAULT NULL, derivado_en VARCHAR(255) DEFAULT NULL, empresa_transporte_derivacion VARCHAR(255) DEFAULT NULL, fecha_alta_por_permiso DATETIME DEFAULT NULL, fecha_baja_por_permiso DATETIME DEFAULT NULL, de_permiso TINYINT(1) DEFAULT NULL, INDEX IDX_A68EC99CDE734E51 (cliente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE habitacion (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, camas_disponibles INT NOT NULL, camas_ocupadas JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE consumible (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, existencia INT NOT NULL, precio DOUBLE PRECISION NOT NULL, unidades VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mail_code (id INT AUTO_INCREMENT NOT NULL, mail VARCHAR(255) NOT NULL, code VARCHAR(255) NOT NULL, type INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE familiar_extra (id INT AUTO_INCREMENT NOT NULL, cliente_id INT NOT NULL, nombre VARCHAR(255) NOT NULL, tel VARCHAR(255) DEFAULT NULL, mail VARCHAR(255) DEFAULT NULL, vinculo VARCHAR(255) DEFAULT NULL, acompanante TINYINT(1) DEFAULT \'0\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE obra_social (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, tel VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cliente (id INT AUTO_INCREMENT NOT NULL, h_clinica INT DEFAULT NULL, nombre VARCHAR(255) NOT NULL, apellido VARCHAR(255) NOT NULL, dni INT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, telefono VARCHAR(255) DEFAULT NULL, f_ingreso DATE DEFAULT NULL, f_egreso DATE DEFAULT NULL, motivo_ing INT DEFAULT NULL, motivo_egr INT DEFAULT NULL, activo TINYINT(1) NOT NULL, viene_de VARCHAR(255) DEFAULT NULL, doc_derivante VARCHAR(255) DEFAULT NULL, modalidad VARCHAR(255) NOT NULL, motivo_ing_especifico VARCHAR(255) DEFAULT NULL, habitacion VARCHAR(255) DEFAULT NULL, n_cama VARCHAR(255) DEFAULT NULL, familiar_responsable_nombre VARCHAR(255) DEFAULT NULL, familiar_responsable_tel VARCHAR(255) DEFAULT NULL, familiar_responsable_mail VARCHAR(255) DEFAULT NULL, familiar_responsable_acompanante TINYINT(1) DEFAULT \'0\' NOT NULL, obra_social VARCHAR(255) DEFAULT NULL, vinculo_responsable VARCHAR(255) DEFAULT NULL, f_nacimiento DATE DEFAULT NULL, edad VARCHAR(255) DEFAULT NULL, sistema_de_emergencia_nombre VARCHAR(255) DEFAULT NULL, sistema_de_emergencia_tel VARCHAR(255) DEFAULT NULL, sistema_de_emergencia_afiliado VARCHAR(255) DEFAULT NULL, obra_social_telefono VARCHAR(255) DEFAULT NULL, obra_social_afiliado VARCHAR(255) DEFAULT NULL, tipo_de_pago VARCHAR(255) DEFAULT NULL, posicion_en_archivo VARCHAR(255) DEFAULT NULL, hab_privada INT DEFAULT NULL, disponible_para_terapia TINYINT(1) DEFAULT \'1\' NOT NULL, derivado TINYINT(1) DEFAULT \'0\' NOT NULL, de_permiso TINYINT(1) DEFAULT \'0\' NOT NULL, derivado_en VARCHAR(255) DEFAULT NULL, fecha_derivacion DATE DEFAULT NULL, motivo_derivacion VARCHAR(255) DEFAULT NULL, emp_traslado_derivacion VARCHAR(255) DEFAULT NULL, fecha_reingreso_derivacion DATETIME DEFAULT NULL, motivo_reingreso_derivacion VARCHAR(255) DEFAULT NULL, fecha_baja_por_permiso DATETIME DEFAULT NULL, fecha_alta_por_permiso DATETIME DEFAULT NULL, terapias_habilitadas JSON DEFAULT NULL, terapias_no_habilitadas JSON DEFAULT NULL, sesiones_disp INT DEFAULT NULL, form_num VARCHAR(255) DEFAULT NULL, vto_sesiones DATE DEFAULT NULL, media_sesion TINYINT(1) DEFAULT NULL, dieta VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_F41C9B2534644328 (h_clinica), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notas_turno (id INT AUTO_INCREMENT NOT NULL, turno_id INT NOT NULL, text LONGTEXT NOT NULL, INDEX IDX_F92A813869C5211E (turno_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE doctor (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, apellido VARCHAR(255) NOT NULL, especialidad JSON NOT NULL, firma VARCHAR(1024) DEFAULT NULL, roles JSON NOT NULL, matricula VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, password VARCHAR(255) NOT NULL, modalidad JSON DEFAULT NULL, vto_contrato DATE DEFAULT NULL, inicio_contrato DATE DEFAULT NULL, tipo VARCHAR(255) NOT NULL, dni VARCHAR(255) NOT NULL, vto_matricula DATE DEFAULT NULL, email VARCHAR(255) NOT NULL, legajo VARCHAR(255) NOT NULL, fecha_baja DATE DEFAULT NULL, motivo_baja VARCHAR(255) DEFAULT NULL, concepto VARCHAR(255) DEFAULT NULL, telefono VARCHAR(255) NOT NULL, libreta_sanitaria VARCHAR(255) DEFAULT NULL, vto_libreta_sanitaria DATE DEFAULT NULL, emision_libreta_sanitaria DATE DEFAULT NULL, posicion_en_archivo VARCHAR(255) DEFAULT NULL, business_hours JSON DEFAULT NULL, cbu VARCHAR(255) DEFAULT NULL, max_cli_turno INT DEFAULT NULL, color VARCHAR(255) DEFAULT NULL, f_nac DATE DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE doctor_cliente (doctor_id INT NOT NULL, cliente_id INT NOT NULL, INDEX IDX_67B1F88887F4FB17 (doctor_id), INDEX IDX_67B1F888DE734E51 (cliente_id), PRIMARY KEY(doctor_id, cliente_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEDE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE historia_paciente ADD CONSTRAINT FK_A68EC99CDE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE notas_turno ADD CONSTRAINT FK_F92A813869C5211E FOREIGN KEY (turno_id) REFERENCES booking (id)');
        $this->addSql('ALTER TABLE doctor_cliente ADD CONSTRAINT FK_67B1F88887F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_cliente ADD CONSTRAINT FK_67B1F888DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEA76ED395');
        $this->addSql('ALTER TABLE notas_turno DROP FOREIGN KEY FK_F92A813869C5211E');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDEDE734E51');
        $this->addSql('ALTER TABLE historia_paciente DROP FOREIGN KEY FK_A68EC99CDE734E51');
        $this->addSql('ALTER TABLE doctor_cliente DROP FOREIGN KEY FK_67B1F888DE734E51');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE87F4FB17');
        $this->addSql('ALTER TABLE doctor_cliente DROP FOREIGN KEY FK_67B1F88887F4FB17');
        $this->addSql('DROP TABLE adjuntos_pacientes');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE consumibles_clientes');
        $this->addSql('DROP TABLE adjuntos_staff');
        $this->addSql('DROP TABLE historia_paciente');
        $this->addSql('DROP TABLE habitacion');
        $this->addSql('DROP TABLE consumible');
        $this->addSql('DROP TABLE mail_code');
        $this->addSql('DROP TABLE familiar_extra');
        $this->addSql('DROP TABLE obra_social');
        $this->addSql('DROP TABLE cliente');
        $this->addSql('DROP TABLE notas_turno');
        $this->addSql('DROP TABLE doctor');
        $this->addSql('DROP TABLE doctor_cliente');
    }
}
