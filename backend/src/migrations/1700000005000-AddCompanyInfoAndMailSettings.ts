import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddCompanyInfoAndMailSettings1700000005000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Add new columns to companies table (only if they don't exist)
    const table = await queryRunner.getTable('companies');
    const hasWhatsappNumber = table?.findColumnByName('whatsapp_number');
    const hasMersisNumber = table?.findColumnByName('mersis_number');
    const hasTradeRegistryNumber = table?.findColumnByName('trade_registry_number');
    const hasTaxOffice = table?.findColumnByName('tax_office');

    if (!hasWhatsappNumber) {
      await queryRunner.query(`
        ALTER TABLE \`companies\`
        ADD COLUMN \`whatsapp_number\` varchar(20) DEFAULT NULL AFTER \`phone\`
      `);
    }

    if (!hasMersisNumber) {
      await queryRunner.query(`
        ALTER TABLE \`companies\`
        ADD COLUMN \`mersis_number\` varchar(50) DEFAULT NULL AFTER \`address\`
      `);
    }

    if (!hasTradeRegistryNumber) {
      await queryRunner.query(`
        ALTER TABLE \`companies\`
        ADD COLUMN \`trade_registry_number\` varchar(50) DEFAULT NULL AFTER \`mersis_number\`
      `);
    }

    if (!hasTaxOffice) {
      await queryRunner.query(`
        ALTER TABLE \`companies\`
        ADD COLUMN \`tax_office\` varchar(255) DEFAULT NULL AFTER \`trade_registry_number\`
      `);
    }

    // Create company_mail_settings table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`company_mail_settings\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`company_id\` char(36) NOT NULL,
        \`smtp_host\` varchar(255) DEFAULT NULL,
        \`smtp_port\` int DEFAULT NULL,
        \`smtp_secure\` tinyint(1) NOT NULL DEFAULT 0,
        \`smtp_username\` varchar(255) DEFAULT NULL,
        \`smtp_password\` varchar(255) DEFAULT NULL,
        \`from_email\` varchar(255) DEFAULT NULL,
        \`from_name\` varchar(255) DEFAULT NULL,
        \`contract_created_template\` text DEFAULT NULL,
        \`payment_received_template\` text DEFAULT NULL,
        \`contract_expiring_template\` text DEFAULT NULL,
        \`payment_reminder_template\` text DEFAULT NULL,
        \`welcome_template\` text DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`UQ_company_mail_settings_company\` (\`company_id\`),
        CONSTRAINT \`FK_company_mail_settings_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`DROP TABLE IF EXISTS \`company_mail_settings\``);
    
    await queryRunner.query(`
      ALTER TABLE \`companies\`
      DROP COLUMN \`tax_office\`,
      DROP COLUMN \`trade_registry_number\`,
      DROP COLUMN \`mersis_number\`,
      DROP COLUMN \`whatsapp_number\`
    `);
  }
}
