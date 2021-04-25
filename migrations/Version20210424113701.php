<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210424113701 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tipo_consumible (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE consumible ADD tipo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE consumible ADD CONSTRAINT FK_81C1D87AA9276E6C FOREIGN KEY (tipo_id) REFERENCES tipo_consumible (id)');
        $this->addSql('CREATE INDEX IDX_81C1D87AA9276E6C ON consumible (tipo_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE consumible DROP FOREIGN KEY FK_81C1D87AA9276E6C');
        $this->addSql('DROP TABLE tipo_consumible');
        $this->addSql('DROP INDEX IDX_81C1D87AA9276E6C ON consumible');
        $this->addSql('ALTER TABLE consumible DROP tipo_id');
    }
}
