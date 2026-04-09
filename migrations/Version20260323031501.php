<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260323031501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE modulo (id INT AUTO_INCREMENT NOT NULL, str_nombre VARCHAR(255) NOT NULL, str_ruta VARCHAR(255) NOT NULL, int_estado INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE permiso_perfil (id INT AUTO_INCREMENT NOT NULL, bool_consultar TINYINT NOT NULL, bool_agregar TINYINT NOT NULL, bool_editar TINYINT NOT NULL, bool_eliminar TINYINT NOT NULL, perfil_id INT NOT NULL, modulo_id INT NOT NULL, INDEX IDX_64144B9C57291544 (perfil_id), INDEX IDX_64144B9CC07F55F5 (modulo_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE permiso_perfil ADD CONSTRAINT FK_64144B9C57291544 FOREIGN KEY (perfil_id) REFERENCES perfil (id)');
        $this->addSql('ALTER TABLE permiso_perfil ADD CONSTRAINT FK_64144B9CC07F55F5 FOREIGN KEY (modulo_id) REFERENCES modulo (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE permiso_perfil DROP FOREIGN KEY FK_64144B9C57291544');
        $this->addSql('ALTER TABLE permiso_perfil DROP FOREIGN KEY FK_64144B9CC07F55F5');
        $this->addSql('DROP TABLE modulo');
        $this->addSql('DROP TABLE permiso_perfil');
    }
}
