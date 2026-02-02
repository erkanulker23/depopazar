import { MigrationInterface, QueryRunner, Table, TableForeignKey } from 'typeorm';

export class AddServicesModule1770000001000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Service Categories
    await queryRunner.createTable(
      new Table({
        name: 'service_categories',
        columns: [
          {
            name: 'id',
            type: 'char',
            length: '36',
            collation: 'utf8mb4_unicode_ci',
            isPrimary: true,
            default: '(UUID())',
          },
          {
            name: 'company_id',
            type: 'char',
            length: '36',
            collation: 'utf8mb4_unicode_ci',
          },
          {
            name: 'name',
            type: 'varchar',
            length: '255',
            collation: 'utf8mb4_unicode_ci',
          },
          {
            name: 'description',
            type: 'text',
            isNullable: true,
          },
          {
            name: 'created_at',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
          },
          {
            name: 'updated_at',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
            onUpdate: 'CURRENT_TIMESTAMP',
          },
        ],
        engine: 'InnoDB',
      }),
      true,
    );

    await queryRunner.createForeignKey(
      'service_categories',
      new TableForeignKey({
        columnNames: ['company_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'companies',
        onDelete: 'CASCADE',
      }),
    );

    // Services
    await queryRunner.createTable(
      new Table({
        name: 'services',
        columns: [
          {
            name: 'id',
            type: 'char',
            length: '36',
            collation: 'utf8mb4_unicode_ci',
            isPrimary: true,
            default: '(UUID())',
          },
          {
            name: 'company_id',
            type: 'char',
            length: '36',
            collation: 'utf8mb4_unicode_ci',
          },
          {
            name: 'category_id',
            type: 'char',
            length: '36',
            collation: 'utf8mb4_unicode_ci',
          },
          {
            name: 'name',
            type: 'varchar',
            length: '255',
            collation: 'utf8mb4_unicode_ci',
          },
          {
            name: 'description',
            type: 'text',
            isNullable: true,
          },
          {
            name: 'unit_price',
            type: 'decimal',
            precision: 10,
            scale: 2,
            default: 0,
          },
          {
            name: 'unit',
            type: 'varchar',
            length: '50',
            isNullable: true,
          },
          {
            name: 'created_at',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
          },
          {
            name: 'updated_at',
            type: 'timestamp',
            default: 'CURRENT_TIMESTAMP',
            onUpdate: 'CURRENT_TIMESTAMP',
          },
        ],
        engine: 'InnoDB',
      }),
      true,
    );

    await queryRunner.createForeignKey(
      'services',
      new TableForeignKey({
        columnNames: ['company_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'companies',
        onDelete: 'CASCADE',
      }),
    );

    await queryRunner.createForeignKey(
      'services',
      new TableForeignKey({
        columnNames: ['category_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'service_categories',
        onDelete: 'CASCADE',
      }),
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropTable('services');
    await queryRunner.dropTable('service_categories');
  }
}
