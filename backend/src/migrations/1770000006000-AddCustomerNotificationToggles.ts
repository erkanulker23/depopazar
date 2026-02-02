import { MigrationInterface, QueryRunner, TableColumn } from 'typeorm';

export class AddCustomerNotificationToggles1770000006000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.addColumns('company_mail_settings', [
      new TableColumn({
        name: 'notify_customer_on_contract',
        type: 'boolean',
        default: true,
      }),
      new TableColumn({
        name: 'notify_customer_on_payment',
        type: 'boolean',
        default: true,
      }),
      new TableColumn({
        name: 'notify_customer_on_overdue',
        type: 'boolean',
        default: true,
      }),
    ]);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.dropColumns('company_mail_settings', [
      'notify_customer_on_contract',
      'notify_customer_on_payment',
      'notify_customer_on_overdue',
    ]);
  }
}
