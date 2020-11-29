<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201125164359 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE doctor_cliente');
        $this->addSql('ALTER TABLE cliente ADD doc_referente_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B25766B17C9 FOREIGN KEY (doc_referente_id) REFERENCES doctor (id)');
        $this->addSql('CREATE INDEX IDX_F41C9B25766B17C9 ON cliente (doc_referente_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE doctor_cliente (doctor_id INT NOT NULL, cliente_id INT NOT NULL, INDEX IDX_67B1F88887F4FB17 (doctor_id), INDEX IDX_67B1F888DE734E51 (cliente_id), PRIMARY KEY(doctor_id, cliente_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE doctor_cliente ADD CONSTRAINT FK_67B1F88887F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE doctor_cliente ADD CONSTRAINT FK_67B1F888DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B25766B17C9');
        $this->addSql('DROP INDEX IDX_F41C9B25766B17C9 ON cliente');
        $this->addSql('ALTER TABLE cliente DROP doc_referente_id');
    }
}
