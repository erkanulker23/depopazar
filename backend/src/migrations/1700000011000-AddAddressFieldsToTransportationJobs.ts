import { MigrationInterface, QueryRunner } from 'typeorm';

export class AddAddressFieldsToTransportationJobs1700000011000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Add pickup_address and delivery_address fields
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      ADD COLUMN \`pickup_address\` text DEFAULT NULL AFTER \`pickup_room_count\`,
      ADD COLUMN \`delivery_address\` text DEFAULT NULL AFTER \`delivery_room_count\`
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    // Remove address fields
    await queryRunner.query(`
      ALTER TABLE \`transportation_jobs\`
      DROP COLUMN \`pickup_address\`,
      DROP COLUMN \`delivery_address\`
    `);
  }
}
