<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308011104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, str_nombre_usuario VARCHAR(50) NOT NULL, str_pwd VARCHAR(255) NOT NULL, id_estado_usuario TINYINT NOT NULL, str_correo VARCHAR(100) NOT NULL, str_numero_celular VARCHAR(15) NOT NULL, foto VARCHAR(255) DEFAULT NULL, perfil_id INT NOT NULL, INDEX IDX_2265B05D57291544 (perfil_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE usuario ADD CONSTRAINT FK_2265B05D57291544 FOREIGN KEY (perfil_id) REFERENCES perfil (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE usuario DROP FOREIGN KEY FK_2265B05D57291544');
        $this->addSql('DROP TABLE usuario');
    }
}
