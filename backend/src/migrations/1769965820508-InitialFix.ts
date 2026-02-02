import { MigrationInterface, QueryRunner } from "typeorm";

export class InitialFix1769965820508 implements MigrationInterface {
    name = 'InitialFix1769965820508'

    public async up(queryRunner: QueryRunner): Promise<void> {
        await queryRunner.query(`SET FOREIGN_KEY_CHECKS = 0`);

        const foreignKeys = await queryRunner.query(`
            SELECT TABLE_NAME, CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        `);

        for (const fk of foreignKeys) {
            try {
                await queryRunner.query(`ALTER TABLE \`${fk.TABLE_NAME}\` DROP FOREIGN KEY \`${fk.CONSTRAINT_NAME}\``);
            } catch (e) {}
        }

        const safeDropIndex = async (table: string, index: string) => {
            try {
                const result = await queryRunner.query(`
                    SELECT INDEX_NAME FROM information_schema.STATISTICS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND INDEX_NAME = '${index}'
                `);
                if (result.length > 0) await queryRunner.query(`DROP INDEX \`${index}\` ON \`${table}\``);
            } catch (e) {}
        };

        const safeDropPK = async (table: string) => {
            try {
                const result = await queryRunner.query(`
                    SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND CONSTRAINT_TYPE = 'PRIMARY KEY'
                `);
                if (result.length > 0) await queryRunner.query(`ALTER TABLE \`${table}\` DROP PRIMARY KEY`);
            } catch (e) {}
        };

        const safeDropColumn = async (table: string, column: string) => {
            try {
                const result = await queryRunner.query(`
                    SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND COLUMN_NAME = '${column}'
                `);
                if (result.length > 0) await queryRunner.query(`ALTER TABLE \`${table}\` DROP COLUMN \`${column}\``);
            } catch (e) {}
        };

        const processTable = async (table: string, idType: string = "varchar(36)") => {
            await safeDropPK(table);
            await safeDropColumn(table, 'id');
            await queryRunner.query(`ALTER TABLE \`${table}\` ADD \`id\` ${idType} NOT NULL`);
            await queryRunner.query(`UPDATE \`${table}\` SET \`id\` = UUID()`);
            await queryRunner.query(`ALTER TABLE \`${table}\` ADD PRIMARY KEY (\`id\`)`);
        };

        // SMS settings - use varchar(36) for company_id compatibility
        await queryRunner.query(`CREATE TABLE IF NOT EXISTS \`company_sms_settings\` (\`id\` varchar(36) NOT NULL, \`created_at\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), \`updated_at\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6), \`deleted_at\` datetime(6) NULL, \`company_id\` varchar(36) NOT NULL, \`username\` varchar(255) NULL, \`password\` varchar(255) NULL, \`sender_id\` varchar(50) NULL, \`api_url\` varchar(255) NULL, \`is_active\` tinyint NOT NULL DEFAULT 0, \`test_mode\` tinyint NOT NULL DEFAULT 0, UNIQUE INDEX \`REL_d2b08509989c8a9869b417f48f\` (\`company_id\`), PRIMARY KEY (\`id\`)) ENGINE=InnoDB`);

        const tablesToProcess = [
            'users', 'bank_accounts', 'companies', 'customers', 'payments', 
            'contract_monthly_prices', 'contract_staff', 'contracts', 'items', 
            'rooms', 'warehouses', 'transportation_job_staff', 'transportation_jobs', 
            'notifications', 'company_paytr_settings', 'company_mail_settings'
        ];

        for (const table of tablesToProcess) {
            await processTable(table);
        }

        // Fix column types for foreign keys to match varchar(36) UUIDs
        await safeDropColumn('users', 'company_id');
        await queryRunner.query(`ALTER TABLE \`users\` ADD \`company_id\` varchar(36) NULL`);
        
        await safeDropColumn('bank_accounts', 'company_id');
        await queryRunner.query(`ALTER TABLE \`bank_accounts\` ADD \`company_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('customers', 'user_id');
        await queryRunner.query(`ALTER TABLE \`customers\` ADD \`user_id\` varchar(36) NULL`);
        await safeDropColumn('customers', 'company_id');
        await queryRunner.query(`ALTER TABLE \`customers\` ADD \`company_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('payments', 'contract_id');
        await queryRunner.query(`ALTER TABLE \`payments\` ADD \`contract_id\` varchar(36) NOT NULL`);
        await safeDropColumn('payments', 'bank_account_id');
        await queryRunner.query(`ALTER TABLE \`payments\` ADD \`bank_account_id\` varchar(36) NULL`);
        
        await safeDropColumn('contract_monthly_prices', 'contract_id');
        await queryRunner.query(`ALTER TABLE \`contract_monthly_prices\` ADD \`contract_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('contract_staff', 'contract_id');
        await queryRunner.query(`ALTER TABLE \`contract_staff\` ADD \`contract_id\` varchar(36) NOT NULL`);
        await safeDropColumn('contract_staff', 'user_id');
        await queryRunner.query(`ALTER TABLE \`contract_staff\` ADD \`user_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('contracts', 'customer_id');
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD \`customer_id\` varchar(36) NOT NULL`);
        await safeDropColumn('contracts', 'room_id');
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD \`room_id\` varchar(36) NOT NULL`);
        await safeDropColumn('contracts', 'sold_by_user_id');
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD \`sold_by_user_id\` varchar(36) NULL`);
        
        await safeDropColumn('items', 'room_id');
        await queryRunner.query(`ALTER TABLE \`items\` ADD \`room_id\` varchar(36) NOT NULL`);
        await safeDropColumn('items', 'contract_id');
        await queryRunner.query(`ALTER TABLE \`items\` ADD \`contract_id\` varchar(36) NULL`);
        
        await safeDropColumn('rooms', 'warehouse_id');
        await queryRunner.query(`ALTER TABLE \`rooms\` ADD \`warehouse_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('warehouses', 'company_id');
        await queryRunner.query(`ALTER TABLE \`warehouses\` ADD \`company_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('transportation_job_staff', 'transportation_job_id');
        await queryRunner.query(`ALTER TABLE \`transportation_job_staff\` ADD \`transportation_job_id\` varchar(36) NOT NULL`);
        await safeDropColumn('transportation_job_staff', 'user_id');
        await queryRunner.query(`ALTER TABLE \`transportation_job_staff\` ADD \`user_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('transportation_jobs', 'company_id');
        await queryRunner.query(`ALTER TABLE \`transportation_jobs\` ADD \`company_id\` varchar(36) NOT NULL`);
        await safeDropColumn('transportation_jobs', 'customer_id');
        await queryRunner.query(`ALTER TABLE \`transportation_jobs\` ADD \`customer_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('notifications', 'user_id');
        await queryRunner.query(`ALTER TABLE \`notifications\` ADD \`user_id\` varchar(36) NULL`);
        await safeDropColumn('notifications', 'customer_id');
        await queryRunner.query(`ALTER TABLE \`notifications\` ADD \`customer_id\` varchar(36) NULL`);
        
        await safeDropColumn('company_paytr_settings', 'company_id');
        await queryRunner.query(`ALTER TABLE \`company_paytr_settings\` ADD \`company_id\` varchar(36) NOT NULL`);
        
        await safeDropColumn('company_mail_settings', 'company_id');
        await queryRunner.query(`ALTER TABLE \`company_mail_settings\` ADD \`company_id\` varchar(36) NOT NULL`);

        // Enum and other adjustments
        await queryRunner.query(`ALTER TABLE \`users\` CHANGE \`role\` \`role\` enum ('super_admin', 'company_owner', 'company_staff', 'customer') NOT NULL DEFAULT 'customer'`);
        await queryRunner.query(`ALTER TABLE \`payments\` CHANGE \`status\` \`status\` enum ('pending', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'pending'`);
        await queryRunner.query(`ALTER TABLE \`rooms\` CHANGE \`status\` \`status\` enum ('empty', 'occupied', 'reserved', 'locked') NOT NULL DEFAULT 'empty'`);
        await queryRunner.query(`ALTER TABLE \`notifications\` CHANGE \`type\` \`type\` enum ('payment_overdue', 'contract_expiring', 'contract_expired', 'contract_created', 'payment_reminder', 'payment_received', 'customer_created', 'room_created', 'room_deleted', 'warehouse_created', 'warehouse_deleted', 'staff_created', 'staff_deleted', 'transportation_job_created', 'transportation_job_updated', 'system') NOT NULL`);

        // Re-add indices and constraints
        await safeDropIndex('companies', 'UQ_companies_slug');
        await queryRunner.query(`ALTER TABLE \`companies\` ADD UNIQUE INDEX \`IDX_b28b07d25e4324eee577de5496\` (\`slug\`)`);
        await safeDropIndex('payments', 'UQ_payments_number');
        await queryRunner.query(`ALTER TABLE \`payments\` ADD UNIQUE INDEX \`IDX_37f40df34aab6084881c0ceebd\` (\`payment_number\`)`);
        await safeDropIndex('contracts', 'UQ_contracts_number');
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD UNIQUE INDEX \`IDX_db84c172dc74e6271e614b68fb\` (\`contract_number\`)`);

        await queryRunner.query(`ALTER TABLE \`users\` ADD CONSTRAINT \`FK_7ae6334059289559722437bcc1c\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`bank_accounts\` ADD CONSTRAINT \`FK_869d5463de72be0afa52f0859e8\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`customers\` ADD CONSTRAINT \`FK_11d81cd7be87b6f8865b0cf7661\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`payments\` ADD CONSTRAINT \`FK_52fc2356fb8c211c93d4b1496f3\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`payments\` ADD CONSTRAINT \`FK_00ec82cb228fb85ddbe768fb6d4\` FOREIGN KEY (\`bank_account_id\`) REFERENCES \`bank_accounts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`contract_monthly_prices\` ADD CONSTRAINT \`FK_f50cfcdf9a2786b227ad3db00c6\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`contract_staff\` ADD CONSTRAINT \`FK_618a7585cbb2740668221784703\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`contract_staff\` ADD CONSTRAINT \`FK_99cc0fdc6261f9d0bcf13b65992\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_2e66f7950711366031e3200413d\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_36b16ffbd8846bd80f7ae72241d\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_5dd5738dac15ca2687138a947fb\` FOREIGN KEY (\`sold_by_user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`items\` ADD CONSTRAINT \`FK_e4b5876b9dc744b77ecfb7f0eef\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`items\` ADD CONSTRAINT \`FK_f64f3ab93032cbb4596c541d5ac\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`rooms\` ADD CONSTRAINT \`FK_b1fd3fe7b80a5176ce8d8659d3f\` FOREIGN KEY (\`warehouse_id\`) REFERENCES \`warehouses\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`warehouses\` ADD CONSTRAINT \`FK_3fcbfd5832b46945f514a7d1f56\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`transportation_job_staff\` ADD CONSTRAINT \`FK_f090ca206307005520fa973a860\` FOREIGN KEY (\`transportation_job_id\`) REFERENCES \`transportation_jobs\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`transportation_job_staff\` ADD CONSTRAINT \`FK_b59b22e5da96680bc7d7ed8a522\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`transportation_jobs\` ADD CONSTRAINT \`FK_5bd54300645fa99e0b115c7362f\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`notifications\` ADD CONSTRAINT \`FK_9a8a82462cab47c73d25f49261f\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`notifications\` ADD CONSTRAINT \`FK_b55350bc786b052e8523f313b9a\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`company_sms_settings\` ADD CONSTRAINT \`FK_d2b08509989c8a9869b417f48f1\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`company_paytr_settings\` ADD CONSTRAINT \`FK_edf59737da13f03e9faa4197433\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);
        await queryRunner.query(`ALTER TABLE \`company_mail_settings\` ADD CONSTRAINT \`FK_eb8bc07a81caea2c83e486a25f3\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`);

        await queryRunner.query(`SET FOREIGN_KEY_CHECKS = 1`);
    }

    public async down(queryRunner: QueryRunner): Promise<void> {
    }
}
