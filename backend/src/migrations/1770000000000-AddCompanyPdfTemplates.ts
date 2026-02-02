import { MigrationInterface, QueryRunner, TableColumn } from 'typeorm';

export class AddCompanyPdfTemplates1770000000000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('companies');
    if (table) {
      if (!table.findColumnByName('contract_template_url')) {
        await queryRunner.addColumn(
          'companies',
          new TableColumn({
            name: 'contract_template_url',
            type: 'varchar',
            length: '512',
            isNullable: true,
          }),
        );
      }
      if (!table.findColumnByName('insurance_template_url')) {
        await queryRunner.addColumn(
          'companies',
          new TableColumn({
            name: 'insurance_template_url',
            type: 'varchar',
            length: '512',
            isNullable: true,
          }),
        );
      }
    }
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('companies');
    if (table) {
      if (table.findColumnByName('contract_template_url')) {
        await queryRunner.dropColumn('companies', 'contract_template_url');
      }
      if (table.findColumnByName('insurance_template_url')) {
        await queryRunner.dropColumn('companies', 'insurance_template_url');
      }
    }
  }
}
