import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddTransportationJobs1700000009000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`transportation_jobs\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`company_id\` char(36) NOT NULL,
        \`customer_id\` char(36) NOT NULL,
        \`pickup_province\` varchar(100) DEFAULT NULL,
        \`pickup_district\` varchar(100) DEFAULT NULL,
        \`pickup_neighborhood\` varchar(100) DEFAULT NULL,
        \`pickup_floor_status\` varchar(50) DEFAULT NULL,
        \`pickup_elevator_status\` varchar(50) DEFAULT NULL,
        \`pickup_room_count\` int DEFAULT NULL,
        \`delivery_province\` varchar(100) DEFAULT NULL,
        \`delivery_district\` varchar(100) DEFAULT NULL,
        \`delivery_neighborhood\` varchar(100) DEFAULT NULL,
        \`delivery_floor_status\` varchar(50) DEFAULT NULL,
        \`delivery_elevator_status\` varchar(50) DEFAULT NULL,
        \`delivery_room_count\` int DEFAULT NULL,
        \`staff_count\` int DEFAULT NULL,
        \`price\` decimal(10,2) DEFAULT NULL,
        \`price_includes_vat\` tinyint(1) NOT NULL DEFAULT 0,
        \`contract_pdf_url\` text DEFAULT NULL,
        \`notes\` text DEFAULT NULL,
        \`status\` varchar(50) NOT NULL DEFAULT 'pending',
        \`job_date\` datetime DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_transportation_jobs_company\` (\`company_id\`),
        KEY \`IDX_transportation_jobs_customer\` (\`customer_id\`),
        CONSTRAINT \`FK_transportation_jobs_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE CASCADE,
        CONSTRAINT \`FK_transportation_jobs_customer\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`DROP TABLE IF EXISTS \`transportation_jobs\``);
  }
}
