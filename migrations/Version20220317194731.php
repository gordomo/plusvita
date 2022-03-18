<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220317194731 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE historia_habitaciones DROP FOREIGN KEY FK_5A3F4F8EB009290D');
        $this->addSql('ALTER TABLE historia_habitaciones CHANGE habitacion_id habitacion_id INT NOT NULL');
        $this->addSql('ALTER TABLE historia_habitaciones ADD CONSTRAINT FK_5A3F4F8EB009290D FOREIGN KEY (habitacion_id) REFERENCES habitacion (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE historia_habitaciones DROP FOREIGN KEY FK_5A3F4F8EB009290D');
        $this->addSql('ALTER TABLE historia_habitaciones CHANGE habitacion_id habitacion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historia_habitaciones ADD CONSTRAINT FK_5A3F4F8EB009290D FOREIGN KEY (habitacion_id) REFERENCES habitacion (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
