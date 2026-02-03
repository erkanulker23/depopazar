import { MigrationInterface, QueryRunner } from 'typeorm';

/**
 * proposals ve proposal_items tablolarına deleted_at (soft delete) sütunu ekler.
 * BaseEntity'de DeleteDateColumn tanımlı olduğu için bu sütunlar gerekli.
 */
export class AddDeletedAtToProposalsTables1770000009000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    const propTable = await queryRunner.getTable('proposals');
    if (propTable && !propTable.findColumnByName('deleted_at')) {
      await queryRunner.query(
        'ALTER TABLE `proposals` ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL',
      );
    }
    const itemTable = await queryRunner.getTable('proposal_items');
    if (itemTable && !itemTable.findColumnByName('deleted_at')) {
      await queryRunner.query(
        'ALTER TABLE `proposal_items` ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL',
      );
    }
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    const propTable = await queryRunner.getTable('proposals');
    if (propTable && propTable.findColumnByName('deleted_at')) {
      await queryRunner.query('ALTER TABLE `proposals` DROP COLUMN `deleted_at`');
    }
    const itemTable = await queryRunner.getTable('proposal_items');
    if (itemTable && itemTable.findColumnByName('deleted_at')) {
      await queryRunner.query('ALTER TABLE `proposal_items` DROP COLUMN `deleted_at`');
    }
  }
}
