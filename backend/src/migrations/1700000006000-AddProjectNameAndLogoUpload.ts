import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddProjectNameAndLogoUpload1700000006000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('companies');
    const hasProjectName = table?.findColumnByName('project_name');
    const logoUrlColumn = table?.findColumnByName('logo_url');

    if (!hasProjectName) {
      await queryRunner.query(`
        ALTER TABLE \`companies\`
        ADD COLUMN \`project_name\` varchar(255) DEFAULT NULL AFTER \`slug\`
      `);
    }

    // Modify logo_url only if it exists and needs to be modified
    if (logoUrlColumn && logoUrlColumn.length && Number(logoUrlColumn.length) !== 512) {
      await queryRunner.query(`
        ALTER TABLE \`companies\`
        MODIFY COLUMN \`logo_url\` varchar(512) DEFAULT NULL
      `);
    }
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`companies\`
      DROP COLUMN \`project_name\`,
      MODIFY COLUMN \`logo_url\` varchar(255) DEFAULT NULL
    `);
  }
}
