import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddContractPdfUrl1700000003000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      ADD COLUMN \`contract_pdf_url\` text NULL
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      DROP COLUMN \`contract_pdf_url\`
    `);
  }
}
