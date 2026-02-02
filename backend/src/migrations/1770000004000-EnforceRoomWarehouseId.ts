import { MigrationInterface, QueryRunner, TableColumn } from 'typeorm';

export class EnforceRoomWarehouseId1770000004000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // First, check if there are any rooms with null warehouse_id (shouldn't be, but safe check)
    // If so, we might need to delete them or assign a default. Assuming data is clean or we delete orphans.
    await queryRunner.query('DELETE FROM rooms WHERE warehouse_id IS NULL');

    // Change column to NOT NULL
    await queryRunner.changeColumn(
      'rooms',
      'warehouse_id',
      new TableColumn({
        name: 'warehouse_id',
        type: 'varchar',
        length: '36',
        collation: 'utf8mb4_unicode_ci',
        isNullable: false,
      }),
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.changeColumn(
      'rooms',
      'warehouse_id',
      new TableColumn({
        name: 'warehouse_id',
        type: 'varchar',
        length: '36',
        collation: 'utf8mb4_unicode_ci',
        isNullable: true,
      }),
    );
  }
}
