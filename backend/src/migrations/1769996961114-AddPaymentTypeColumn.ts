import { MigrationInterface, QueryRunner } from "typeorm";

export class AddPaymentTypeColumn1769996961114 implements MigrationInterface {
    name = 'AddPaymentTypeColumn1769996961114'

    public async up(queryRunner: QueryRunner): Promise<void> {
        await queryRunner.query(`SET FOREIGN_KEY_CHECKS = 0`);

        // Dinamik FK Temizliği
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

        // Yardımcı Fonksiyonlar
        const safeAddIndex = async (table: string, indexName: string, column: string, unique: boolean = false) => {
            const result = await queryRunner.query(`
                SELECT INDEX_NAME FROM information_schema.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND INDEX_NAME = '${indexName}'
            `);
            if (result.length === 0) {
                const type = unique ? 'UNIQUE INDEX' : 'INDEX';
                await queryRunner.query(`ALTER TABLE \`${table}\` ADD ${type} \`${indexName}\` (\`${column}\`)`);
            }
        };

        const safeAddConstraint = async (table: string, constraintName: string, query: string) => {
            const result = await queryRunner.query(`
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND CONSTRAINT_NAME = '${constraintName}'
            `);
            if (result.length === 0) {
                await queryRunner.query(query);
            }
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

        // Bu migrasyonun asıl amacı: type kolonu ekleme
        const hasPaymentType = await queryRunner.query(`SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'type'`);
        if (hasPaymentType.length === 0) {
            await queryRunner.query(`ALTER TABLE \`payments\` ADD \`type\` enum ('warehouse', 'transportation', 'other') NOT NULL DEFAULT 'warehouse'`);
        }

        // Tablo yapılarını standart hale getir (Varlık kontrolü ile)
        const processTable = async (table: string, idType: string = "varchar(36)") => {
            await safeDropPK(table);
            await safeDropColumn(table, 'id');
            await queryRunner.query(`ALTER TABLE \`${table}\` ADD \`id\` ${idType} NOT NULL`);
            await queryRunner.query(`UPDATE \`${table}\` SET \`id\` = UUID()`);
            await queryRunner.query(`ALTER TABLE \`${table}\` ADD PRIMARY KEY (\`id\`)`);
        };

        const tablesToProcess = [
            'users', 'bank_accounts', 'companies', 'customers', 'payments', 
            'contract_monthly_prices', 'contract_staff', 'contracts', 'items', 
            'rooms', 'warehouses', 'transportation_job_staff', 'transportation_jobs', 
            'notifications', 'company_paytr_settings', 'company_mail_settings'
        ];

        for (const table of tablesToProcess) {
            await processTable(table);
        }

        // Kısıtlamaları güvenli bir şekilde ekle
        const constraints = [
            {table: 'users', name: 'FK_7ae6334059289559722437bcc1c', query: `ALTER TABLE \`users\` ADD CONSTRAINT \`FK_7ae6334059289559722437bcc1c\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'bank_accounts', name: 'FK_869d5463de72be0afa52f0859e8', query: `ALTER TABLE \`bank_accounts\` ADD CONSTRAINT \`FK_869d5463de72be0afa52f0859e8\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'customers', name: 'FK_11d81cd7be87b6f8865b0cf7661', query: `ALTER TABLE \`customers\` ADD CONSTRAINT \`FK_11d81cd7be87b6f8865b0cf7661\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'payments', name: 'FK_52fc2356fb8c211c93d4b1496f3', query: `ALTER TABLE \`payments\` ADD CONSTRAINT \`FK_52fc2356fb8c211c93d4b1496f3\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'payments', name: 'FK_00ec82cb228fb85ddbe768fb6d4', query: `ALTER TABLE \`payments\` ADD CONSTRAINT \`FK_00ec82cb228fb85ddbe768fb6d4\` FOREIGN KEY (\`bank_account_id\`) REFERENCES \`bank_accounts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'contract_monthly_prices', name: 'FK_f50cfcdf9a2786b227ad3db00c6', query: `ALTER TABLE \`contract_monthly_prices\` ADD CONSTRAINT \`FK_f50cfcdf9a2786b227ad3db00c6\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'contract_staff', name: 'FK_618a7585cbb2740668221784703', query: `ALTER TABLE \`contract_staff\` ADD CONSTRAINT \`FK_618a7585cbb2740668221784703\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'contract_staff', name: 'FK_99cc0fdc6261f9d0bcf13b65992', query: `ALTER TABLE \`contract_staff\` ADD CONSTRAINT \`FK_99cc0fdc6261f9d0bcf13b65992\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'contracts', name: 'FK_2e66f7950711366031e3200413d', query: `ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_2e66f7950711366031e3200413d\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'contracts', name: 'FK_36b16ffbd8846bd80f7ae72241d', query: `ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_36b16ffbd8846bd80f7ae72241d\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'contracts', name: 'FK_5dd5738dac15ca2687138a947fb', query: `ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_5dd5738dac15ca2687138a947fb\` FOREIGN KEY (\`sold_by_user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'items', name: 'FK_e4b5876b9dc744b77ecfb7f0eef', query: `ALTER TABLE \`items\` ADD CONSTRAINT \`FK_e4b5876b9dc744b77ecfb7f0eef\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'items', name: 'FK_f64f3ab93032cbb4596c541d5ac', query: `ALTER TABLE \`items\` ADD CONSTRAINT \`FK_f64f3ab93032cbb4596c541d5ac\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'rooms', name: 'FK_b1fd3fe7b80a5176ce8d8659d3f', query: `ALTER TABLE \`rooms\` ADD CONSTRAINT \`FK_b1fd3fe7b80a5176ce8d8659d3f\` FOREIGN KEY (\`warehouse_id\`) REFERENCES \`warehouses\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'warehouses', name: 'FK_3fcbfd5832b46945f514a7d1f56', query: `ALTER TABLE \`warehouses\` ADD CONSTRAINT \`FK_3fcbfd5832b46945f514a7d1f56\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'transportation_job_staff', name: 'FK_f090ca206307005520fa973a860', query: `ALTER TABLE \`transportation_job_staff\` ADD CONSTRAINT \`FK_f090ca206307005520fa973a860\` FOREIGN KEY (\`transportation_job_id\`) REFERENCES \`transportation_jobs\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'transportation_job_staff', name: 'FK_b59b22e5da96680bc7d7ed8a522', query: `ALTER TABLE \`transportation_job_staff\` ADD CONSTRAINT \`FK_b59b22e5da96680bc7d7ed8a522\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'transportation_jobs', name: 'FK_5bd54300645fa99e0b115c7362f', query: `ALTER TABLE \`transportation_jobs\` ADD CONSTRAINT \`FK_5bd54300645fa99e0b115c7362f\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'notifications', name: 'FK_9a8a82462cab47c73d25f49261f', query: `ALTER TABLE \`notifications\` ADD CONSTRAINT \`FK_9a8a82462cab47c73d25f49261f\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'notifications', name: 'FK_b55350bc786b052e8523f313b9a', query: `ALTER TABLE \`notifications\` ADD CONSTRAINT \`FK_b55350bc786b052e8523f313b9a\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'company_sms_settings', name: 'FK_d2b08509989c8a9869b417f48f1', query: `ALTER TABLE \`company_sms_settings\` ADD CONSTRAINT \`FK_d2b08509989c8a9869b417f48f1\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'company_paytr_settings', name: 'FK_edf59737da13f03e9faa4197433', query: `ALTER TABLE \`company_paytr_settings\` ADD CONSTRAINT \`FK_edf59737da13f03e9faa4197433\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`},
            {table: 'company_mail_settings', name: 'FK_eb8bc07a81caea2c83e486a25f3', query: `ALTER TABLE \`company_mail_settings\` ADD CONSTRAINT \`FK_eb8bc07a81caea2c83e486a25f3\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`) ON DELETE NO ACTION ON UPDATE NO ACTION`}
        ];

        for (const c of constraints) {
            await safeAddConstraint(c.table, c.name, c.query);
        }

        await queryRunner.query(`SET FOREIGN_KEY_CHECKS = 1`);
    }

    public async down(queryRunner: QueryRunner): Promise<void> {
    }
}
