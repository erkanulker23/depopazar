import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddPaytrSettings1700000001000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`company_paytr_settings\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`company_id\` char(36) NOT NULL,
        \`merchant_id\` varchar(255) DEFAULT NULL,
        \`merchant_key\` varchar(255) DEFAULT NULL,
        \`merchant_salt\` varchar(255) DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 0,
        \`test_mode\` tinyint(1) NOT NULL DEFAULT 0,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`UQ_paytr_settings_company\` (\`company_id\`),
        KEY \`IDX_paytr_settings_company\` (\`company_id\`),
        CONSTRAINT \`FK_paytr_settings_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`DROP TABLE IF EXISTS \`company_paytr_settings\``);
  }
}
