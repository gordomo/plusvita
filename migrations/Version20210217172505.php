<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210217172505 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE consumible_cliente');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE consumible_cliente (consumible_id INT NOT NULL, cliente_id INT NOT NULL, INDEX IDX_73DAEE119AA59506 (consumible_id), INDEX IDX_73DAEE11DE734E51 (cliente_id), PRIMARY KEY(consumible_id, cliente_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE consumible_cliente ADD CONSTRAINT FK_73DAEE119AA59506 FOREIGN KEY (consumible_id) REFERENCES consumible (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE consumible_cliente ADD CONSTRAINT FK_73DAEE11DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
