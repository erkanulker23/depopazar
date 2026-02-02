import { MigrationInterface, QueryRunner, Table, TableForeignKey } from 'typeorm';

export class AddProposalsModule1770000002000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Proposals
    await queryRunner.createTable(
      new Table({
        name: 'proposals',
        columns: [
          {
            name: 'id',
            type: 'char',
            length: '36',
            isPrimary: true,
            default: '(UUID())',
          },
          {
            name: 'company_id',
            type: 'char',
            length: '36',
          },
          {
            name: 'customer_id',
            type: 'char',
            length: '36',
            isNullable: true,
          },
          {
            name: 'title',
            type: 'varchar',
            length: '255',
          },
          {
            name: 'status',
            type: 'varchar',
            length: '50',
            default: "'draft'",
          },
          {
            name: 'total_amount',
            type: 'decimal',
            precision: 10,
            scale: 2,
            default: 0,
          },
          {
            name: 'currency',
            type: 'varchar',
            length: '10',
            default: "'TRY'",
          },
          {
            name: 'valid_until',
            type: 'datetime',
            isNullable: true,
          },
          {
            name: 'notes',
            type: 'text',
            isNullable: true,
          },
          {
            name: 'pdf_url',
            type: 'varchar',
            length: '512',
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
      }),
      true,
    );

    await queryRunner.createForeignKey(
      'proposals',
      new TableForeignKey({
        columnNames: ['company_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'companies',
        onDelete: 'CASCADE',
      }),
    );

    await queryRunner.createForeignKey(
      'proposals',
      new TableForeignKey({
        columnNames: ['customer_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'customers',
        onDelete: 'SET NULL',
      }),
    );

    // Proposal Items
    await queryRunner.createTable(
      new Table({
        name: 'proposal_items',
        columns: [
          {
            name: 'id',
            type: 'char',
            length: '36',
            isPrimary: true,
            default: '(UUID())',
          },
          {
            name: 'proposal_id',
            type: 'char',
            length: '36',
          },
          {
            name: 'service_id',
            type: 'char',
            length: '36',
            isNullable: true,
          },
          {
            name: 'name',
            type: 'varchar',
            length: '255',
          },
          {
            name: 'description',
            type: 'text',
            isNullable: true,
          },
          {
            name: 'quantity',
            type: 'decimal',
            precision: 10,
            scale: 2,
          },
          {
            name: 'unit_price',
            type: 'decimal',
            precision: 10,
            scale: 2,
          },
          {
            name: 'total_price',
            type: 'decimal',
            precision: 10,
            scale: 2,
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
      }),
      true,
    );

    await queryRunner.createForeignKey(
      'proposal_items',
      new TableForeignKey({
        columnNames: ['proposal_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'proposals',
        onDelete: 'CASCADE',
      }),
    );

    await queryRunner.createForeignKey(
      'proposal_items',
      new TableForeignKey({
        columnNames: ['service_id'],
        referencedColumnNames: ['id'],
        referencedTableName: 'services',
        onDelete: 'SET NULL',
      }),
    );
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropTable('proposal_items');
    await queryRunner.dropTable('proposals');
  }
}
