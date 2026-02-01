import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddBankAccounts1700000008000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Bank accounts table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`bank_accounts\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`company_id\` char(36) NOT NULL,
        \`bank_name\` varchar(255) NOT NULL,
        \`account_holder_name\` varchar(255) NOT NULL,
        \`account_number\` varchar(50) NOT NULL,
        \`iban\` varchar(34) DEFAULT NULL,
        \`branch_name\` varchar(255) DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_bank_accounts_company\` (\`company_id\`),
        CONSTRAINT \`FK_bank_accounts_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Add bank_account_id to payments table (only if it doesn't exist)
    const paymentsTable = await queryRunner.getTable('payments');
    const hasBankAccountId = paymentsTable?.findColumnByName('bank_account_id');
    
    if (!hasBankAccountId) {
      await queryRunner.query(`
        ALTER TABLE \`payments\`
        ADD COLUMN \`bank_account_id\` char(36) DEFAULT NULL AFTER \`days_overdue\`,
        ADD KEY \`IDX_payments_bank_account\` (\`bank_account_id\`),
        ADD CONSTRAINT \`FK_payments_bank_account\` FOREIGN KEY (\`bank_account_id\`) REFERENCES \`bank_accounts\` (\`id\`) ON DELETE SET NULL
      `);
    }
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    // Remove bank_account_id from payments table
    await queryRunner.query(`
      ALTER TABLE \`payments\`
      DROP FOREIGN KEY \`FK_payments_bank_account\`,
      DROP KEY \`IDX_payments_bank_account\`,
      DROP COLUMN \`bank_account_id\`
    `);

    // Drop bank_accounts table
    await queryRunner.query(`DROP TABLE IF EXISTS \`bank_accounts\``);
  }
}
