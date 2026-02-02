import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddTransportTermsToProposals1770000007000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`proposals\`
      ADD COLUMN \`transport_terms\` text NULL
      AFTER \`notes\`
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`proposals\`
      DROP COLUMN \`transport_terms\`
    `);
  }
}
