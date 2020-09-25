<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200925122241 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE historia_paciente DROP FOREIGN KEY FK_A68EC99CDB38439E');
        $this->addSql('DROP INDEX UNIQ_A68EC99CDB38439E ON historia_paciente');
        $this->addSql('ALTER TABLE historia_paciente ADD usuario VARCHAR(255) NOT NULL, CHANGE usuario_id cliente_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historia_paciente ADD CONSTRAINT FK_A68EC99CDE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A68EC99CDE734E51 ON historia_paciente (cliente_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE historia_paciente DROP FOREIGN KEY FK_A68EC99CDE734E51');
        $this->addSql('DROP INDEX UNIQ_A68EC99CDE734E51 ON historia_paciente');
        $this->addSql('ALTER TABLE historia_paciente DROP usuario, CHANGE cliente_id usuario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historia_paciente ADD CONSTRAINT FK_A68EC99CDB38439E FOREIGN KEY (usuario_id) REFERENCES cliente (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A68EC99CDB38439E ON historia_paciente (usuario_id)');
    }
}
