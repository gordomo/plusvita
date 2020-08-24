<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200824125649 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente ADD sistema_de_emergencia_nombre VARCHAR(255) DEFAULT NULL, ADD sistema_de_emergencia_tel VARCHAR(255) DEFAULT NULL, ADD sistema_de_emergencia_afiliado VARCHAR(255) DEFAULT NULL, ADD obra_social_telefono VARCHAR(255) DEFAULT NULL, ADD obra_social_afiliado VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente DROP sistema_de_emergencia_nombre, DROP sistema_de_emergencia_tel, DROP sistema_de_emergencia_afiliado, DROP obra_social_telefono, DROP obra_social_afiliado');
    }
}
