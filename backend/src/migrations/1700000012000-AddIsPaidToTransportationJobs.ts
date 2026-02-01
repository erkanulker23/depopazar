import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddIsPaidToTransportationJobs1700000012000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      ADD COLUMN \`is_paid\` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Ödeme alındı mı?' AFTER \`job_date\`
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      DROP COLUMN \`is_paid\`
    `);
  }
}
