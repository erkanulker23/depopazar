import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddContractStaffTable1700000004000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Contract staff junction table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`contract_staff\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`contract_id\` char(36) NOT NULL,
        \`user_id\` char(36) NOT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_contract_staff_contract\` (\`contract_id\`),
        KEY \`IDX_contract_staff_user\` (\`user_id\`),
        UNIQUE KEY \`UQ_contract_staff\` (\`contract_id\`, \`user_id\`),
        CONSTRAINT \`FK_contract_staff_contract\`
        FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\` (\`id\`) ON DELETE CASCADE,
        CONSTRAINT \`FK_contract_staff_user\`
        FOREIGN KEY (\`user_id\`) REFERENCES \`users\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`DROP TABLE IF EXISTS \`contract_staff\``);
  }
}
