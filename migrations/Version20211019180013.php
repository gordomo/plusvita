<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211019180013 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notas_historia_clinica DROP FOREIGN KEY FK_A1A5A1F4DC2902E0');
        $this->addSql('DROP INDEX IDX_A1A5A1F4DC2902E0 ON notas_historia_clinica');
        $this->addSql('ALTER TABLE notas_historia_clinica ADD cliente_id INT DEFAULT NULL, DROP client_id_id');
        $this->addSql('ALTER TABLE notas_historia_clinica ADD CONSTRAINT FK_A1A5A1F4DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('CREATE INDEX IDX_A1A5A1F4DE734E51 ON notas_historia_clinica (cliente_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE notas_historia_clinica DROP FOREIGN KEY FK_A1A5A1F4DE734E51');
        $this->addSql('DROP INDEX IDX_A1A5A1F4DE734E51 ON notas_historia_clinica');
        $this->addSql('ALTER TABLE notas_historia_clinica ADD client_id_id INT NOT NULL, DROP cliente_id');
        $this->addSql('ALTER TABLE notas_historia_clinica ADD CONSTRAINT FK_A1A5A1F4DC2902E0 FOREIGN KEY (client_id_id) REFERENCES cliente (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A1A5A1F4DC2902E0 ON notas_historia_clinica (client_id_id)');
    }
}
