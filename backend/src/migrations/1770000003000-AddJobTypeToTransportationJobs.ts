import { MigrationInterface, QueryRunner, TableColumn } from 'typeorm';

export class AddJobTypeToTransportationJobs1770000003000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('transportation_jobs');
    if (table && !table.findColumnByName('job_type')) {
      await queryRunner.addColumn(
        'transportation_jobs',
        new TableColumn({
          name: 'job_type',
          type: 'varchar',
          length: '100',
          isNullable: true,
        }),
      );
    }
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    const table = await queryRunner.getTable('transportation_jobs');
    if (table && table.findColumnByName('job_type')) {
      await queryRunner.dropColumn('transportation_jobs', 'job_type');
    }
  }
}
