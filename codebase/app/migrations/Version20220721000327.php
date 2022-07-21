<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220721000327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE node (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE node_node (node_source BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', node_target BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_42DB65D3EB986AD6 (node_source), INDEX IDX_42DB65D3F27D3A59 (node_target), PRIMARY KEY(node_source, node_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE node_node ADD CONSTRAINT FK_42DB65D3EB986AD6 FOREIGN KEY (node_source) REFERENCES node (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE node_node ADD CONSTRAINT FK_42DB65D3F27D3A59 FOREIGN KEY (node_target) REFERENCES node (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE node_node DROP FOREIGN KEY FK_42DB65D3EB986AD6');
        $this->addSql('ALTER TABLE node_node DROP FOREIGN KEY FK_42DB65D3F27D3A59');
        $this->addSql('DROP TABLE node');
        $this->addSql('DROP TABLE node_node');
    }
}
