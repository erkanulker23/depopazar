import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddConditionToItems1700000007000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Add condition column to items table
    await queryRunner.query(`
      ALTER TABLE \`items\`
      ADD COLUMN \`condition\` varchar(50) DEFAULT 'new' NULL
      AFTER \`unit\`
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    // Remove condition column from items table
    await queryRunner.query(`
      ALTER TABLE \`items\`
      DROP COLUMN \`condition\`
    `);
  }
}
