<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200925143745 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B25F8FA80EF');
        $this->addSql('DROP INDEX UNIQ_F41C9B25F8FA80EF ON cliente');
        $this->addSql('ALTER TABLE cliente DROP historia_id');
        $this->addSql('ALTER TABLE historia_paciente DROP INDEX UNIQ_A68EC99CDE734E51, ADD INDEX IDX_A68EC99CDE734E51 (cliente_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente ADD historia_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B25F8FA80EF FOREIGN KEY (historia_id) REFERENCES historia_paciente (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F41C9B25F8FA80EF ON cliente (historia_id)');
        $this->addSql('ALTER TABLE historia_paciente DROP INDEX IDX_A68EC99CDE734E51, ADD UNIQUE INDEX UNIQ_A68EC99CDE734E51 (cliente_id)');
    }
}
