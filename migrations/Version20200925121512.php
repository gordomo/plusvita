<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200925121512 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente ADD historia_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B25F8FA80EF FOREIGN KEY (historia_id) REFERENCES historia_paciente (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F41C9B25F8FA80EF ON cliente (historia_id)');
        $this->addSql('ALTER TABLE historia_paciente ADD usuario_id INT DEFAULT NULL, DROP usuario');
        $this->addSql('ALTER TABLE historia_paciente ADD CONSTRAINT FK_A68EC99CDB38439E FOREIGN KEY (usuario_id) REFERENCES cliente (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A68EC99CDB38439E ON historia_paciente (usuario_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B25F8FA80EF');
        $this->addSql('DROP INDEX UNIQ_F41C9B25F8FA80EF ON cliente');
        $this->addSql('ALTER TABLE cliente DROP historia_id');
        $this->addSql('ALTER TABLE historia_paciente DROP FOREIGN KEY FK_A68EC99CDB38439E');
        $this->addSql('DROP INDEX UNIQ_A68EC99CDB38439E ON historia_paciente');
        $this->addSql('ALTER TABLE historia_paciente ADD usuario VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, DROP usuario_id');
    }
}
