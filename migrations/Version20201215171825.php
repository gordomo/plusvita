<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201215171825 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente ADD disponible_para_terapia TINYINT(1) DEFAULT \'1\' NOT NULL, ADD derivado TINYINT(1) DEFAULT \'0\' NOT NULL, ADD de_permiso TINYINT(1) DEFAULT \'0\' NOT NULL, ADD derivado_en VARCHAR(255) DEFAULT NULL, ADD fecha_derivacion DATE DEFAULT NULL, ADD motivo_derivacion VARCHAR(255) DEFAULT NULL, ADD emp_traslado_derivacion VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE cliente DROP disponible_para_terapia, DROP derivado, DROP de_permiso, DROP derivado_en, DROP fecha_derivacion, DROP motivo_derivacion, DROP emp_traslado_derivacion');
    }
}
