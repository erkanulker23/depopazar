import { MigrationInterface, QueryRunner } from 'typeorm';

export class UpdateTransportationJobs1700000010000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Add vat_rate column
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      ADD COLUMN \`vat_rate\` decimal(5,2) NOT NULL DEFAULT 20.0 AFTER \`price\`
    `);

    // Remove staff_count column
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      DROP COLUMN \`staff_count\`
    `);

    // Create transportation_job_staff table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`transportation_job_staff\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`transportation_job_id\` char(36) NOT NULL,
        \`user_id\` char(36) NOT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_transportation_job_staff_job\` (\`transportation_job_id\`),
        KEY \`IDX_transportation_job_staff_user\` (\`user_id\`),
        CONSTRAINT \`FK_transportation_job_staff_job\` FOREIGN KEY (\`transportation_job_id\`) REFERENCES \`transportation_jobs\` (\`id\`) ON DELETE CASCADE,
        CONSTRAINT \`FK_transportation_job_staff_user\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    // Drop transportation_job_staff table
    await queryRunner.query(`DROP TABLE IF EXISTS \`transportation_job_staff\``);

    // Add back staff_count column
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      ADD COLUMN \`staff_count\` int DEFAULT NULL
    `);

    // Remove vat_rate column
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      DROP COLUMN \`vat_rate\`
    `);
  }
}
