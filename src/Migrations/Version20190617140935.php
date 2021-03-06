<?php

declare(strict_types=1);

/*
 * This file is part of aakb/jira_economics.
 *
 * (c) 2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190617140935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, att VARCHAR(255) NOT NULL, cvr INT NOT NULL, ean VARCHAR(16) NOT NULL, debtor INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, jira_key VARCHAR(255) NOT NULL, jira_id INT NOT NULL, avatar_url VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, recorded TINYINT(1) NOT NULL, created DATETIME NOT NULL, INDEX IDX_90651744166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_entry (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, account VARCHAR(255) NOT NULL, product VARCHAR(255) DEFAULT NULL, INDEX IDX_16FBCDC52989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE jira_issue (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, issue_id INT NOT NULL, summary VARCHAR(255) NOT NULL, created DATETIME NOT NULL, finished DATETIME DEFAULT NULL, jira_users LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', time_spent INT DEFAULT NULL, invoiceEntryId INT DEFAULT NULL, INDEX IDX_3F6C748D166D1F9C (project_id), INDEX IDX_3F6C748D684BD90 (invoiceEntryId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE invoice_entry ADD CONSTRAINT FK_16FBCDC52989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE jira_issue ADD CONSTRAINT FK_3F6C748D166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE jira_issue ADD CONSTRAINT FK_3F6C748D684BD90 FOREIGN KEY (invoiceEntryId) REFERENCES invoice_entry (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744166D1F9C');
        $this->addSql('ALTER TABLE jira_issue DROP FOREIGN KEY FK_3F6C748D166D1F9C');
        $this->addSql('ALTER TABLE invoice_entry DROP FOREIGN KEY FK_16FBCDC52989F1FD');
        $this->addSql('ALTER TABLE jira_issue DROP FOREIGN KEY FK_3F6C748D684BD90');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_entry');
        $this->addSql('DROP TABLE jira_issue');
    }
}
