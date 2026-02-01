import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddDiscountAndTransportFields1700000002000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Contract tablosuna yeni alanlar ekle
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      ADD COLUMN \`discount\` decimal(10,2) DEFAULT 0 NULL,
      ADD COLUMN \`driver_name\` varchar(100) NULL,
      ADD COLUMN \`driver_phone\` varchar(20) NULL,
      ADD COLUMN \`vehicle_plate\` varchar(20) NULL
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`
      ALTER TABLE \`contracts\`
      DROP COLUMN \`discount\`,
      DROP COLUMN \`driver_name\`,
      DROP COLUMN \`driver_phone\`,
      DROP COLUMN \`vehicle_plate\`
    `);
  }
}
