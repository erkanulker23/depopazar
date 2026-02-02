import { MigrationInterface, QueryRunner, TableColumn } from 'typeorm';

export class AddAdminMailSettings1770000005000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.addColumns('company_mail_settings', [
      new TableColumn({
        name: 'notify_admin_on_contract',
        type: 'boolean',
        default: true,
      }),
      new TableColumn({
        name: 'notify_admin_on_payment',
        type: 'boolean',
        default: true,
      }),
      new TableColumn({
        name: 'admin_contract_created_template',
        type: 'text',
        isNullable: true,
      }),
      new TableColumn({
        name: 'admin_payment_received_template',
        type: 'text',
        isNullable: true,
      }),
    ]);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropColumns('company_mail_settings', [
      'notify_admin_on_contract',
      'notify_admin_on_payment',
      'admin_contract_created_template',
      'admin_payment_received_template',
    ]);
  }
}
