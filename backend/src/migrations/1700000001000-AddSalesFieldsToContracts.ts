import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddSalesFieldsToContracts1700000001000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Contract tablosuna yeni alanlar ekle
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      ADD COLUMN \`transportation_fee\` decimal(10,2) DEFAULT 0 NULL,
      ADD COLUMN \`pickup_location\` varchar(255) NULL,
      ADD COLUMN \`sold_by_user_id\` char(36) NULL
    `);

    // Foreign key ekle
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      ADD CONSTRAINT \`FK_contracts_sold_by_user\`
      FOREIGN KEY (\`sold_by_user_id\`) REFERENCES \`users\` (\`id\`) ON DELETE SET NULL
    `);

    // Contract monthly prices tablosu oluştur
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`contract_monthly_prices\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`contract_id\` char(36) NOT NULL,
        \`month\` varchar(7) NOT NULL,
        \`price\` decimal(10,2) NOT NULL,
        \`notes\` text NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_contract_monthly_prices_contract\` (\`contract_id\`),
        KEY \`IDX_contract_monthly_prices_month\` (\`month\`),
        CONSTRAINT \`FK_contract_monthly_prices_contract\`
        FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`DROP TABLE IF EXISTS \`contract_monthly_prices\``);
    
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      DROP FOREIGN KEY \`FK_contracts_sold_by_user\`
    `);

    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      DROP COLUMN \`transportation_fee\`,
      DROP COLUMN \`pickup_location\`,
      DROP COLUMN \`sold_by_user_id\`
    `);
  }
}
