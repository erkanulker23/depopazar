import { MigrationInterface, QueryRunner } from 'typeorm';

/**
 * service_categories ve services tablolarına deleted_at (soft delete) sütunu ekler.
 * BaseEntity'de DeleteDateColumn tanımlı olduğu için bu sütunlar gerekli.
 */
export class AddDeletedAtToServiceTables1770000008000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    const catTable = await queryRunner.getTable('service_categories');
    if (catTable && !catTable.findColumnByName('deleted_at')) {
      await queryRunner.query(
        'ALTER TABLE `service_categories` ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL',
      );
    }
    const svcTable = await queryRunner.getTable('services');
    if (svcTable && !svcTable.findColumnByName('deleted_at')) {
      await queryRunner.query(
        'ALTER TABLE `services` ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL',
      );
    }
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    const catTable = await queryRunner.getTable('service_categories');
    if (catTable && catTable.findColumnByName('deleted_at')) {
      await queryRunner.query('ALTER TABLE `service_categories` DROP COLUMN `deleted_at`');
    }
    const svcTable = await queryRunner.getTable('services');
    if (svcTable && svcTable.findColumnByName('deleted_at')) {
      await queryRunner.query('ALTER TABLE `services` DROP COLUMN `deleted_at`');
    }
  }
}
