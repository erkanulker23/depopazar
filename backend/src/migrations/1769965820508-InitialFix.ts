import { MigrationInterface, QueryRunner } from "typeorm";

export class InitialFix1769965820508 implements MigrationInterface {
    name = 'InitialFix1769965820508'

    public async up(queryRunner: QueryRunner): Promise<void> {
        await queryRunner.query(`SET FOREIGN_KEY_CHECKS = 0`);

        // 1. Dinamik FK Temizliği
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

        // 2. Yardımcı Fonksiyonlar
        const UUID_TYPE = "varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        const safeAddConstraint = async (table: string, constraintName: string, query: string) => {
            const result = await queryRunner.query(`
                SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND CONSTRAINT_NAME = '${constraintName}'
            `);
            if (result.length === 0) {
                try {
                    await queryRunner.query(query);
                } catch (e) {
                    console.error(`Failed to add constraint ${constraintName} on ${table}:`, e.message);
                }
            }
        };

        const safeModifyColumn = async (table: string, column: string, type: string, nullable: boolean = false) => {
            try {
                await queryRunner.query(`ALTER TABLE \`${table}\` MODIFY \`${column}\` ${type} ${nullable ? 'NULL' : 'NOT NULL'}`);
            } catch (e) {
                // Eğer MODIFY başarısız olursa (kolon yoksa), ADD yapalım
                try {
                    await queryRunner.query(`ALTER TABLE \`${table}\` ADD \`${column}\` ${type} ${nullable ? 'NULL' : 'NOT NULL'}`);
                } catch (addError) {}
            }
        };

        const processTableIds = async (table: string) => {
            // Önce primary key'i düşür
            try {
                const pkResult = await queryRunner.query(`
                    SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '${table}' AND CONSTRAINT_TYPE = 'PRIMARY KEY'
                `);
                if (pkResult.length > 0) {
                    await queryRunner.query(`ALTER TABLE \`${table}\` DROP PRIMARY KEY`);
                }
            } catch (e) {}

            // ID kolonunu UUID tipine modify et
            await safeModifyColumn(table, 'id', UUID_TYPE, false);
            
            // Verileri UUID yap (Eğer boşsa veya format hatalıysa)
            await queryRunner.query(`UPDATE \`${table}\` SET \`id\` = UUID() WHERE \`id\` IS NULL OR LENGTH(\`id\`) < 36`);
            
            // Tekrar PK yap
            await queryRunner.query(`ALTER TABLE \`${table}\` ADD PRIMARY KEY (\`id\`)`);
        };

        // 3. SMS Settings tablosunu garantiye al
        await queryRunner.query(`CREATE TABLE IF NOT EXISTS \`company_sms_settings\` (
            \`id\` ${UUID_TYPE} NOT NULL, 
            \`created_at\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6), 
            \`updated_at\` datetime(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6), 
            \`deleted_at\` datetime(6) NULL, 
            \`company_id\` ${UUID_TYPE} NOT NULL, 
            \`username\` varchar(255) NULL, 
            \`password\` varchar(255) NULL, 
            \`sender_id\` varchar(50) NULL, 
            \`api_url\` varchar(255) NULL, 
            \`is_active\` tinyint NOT NULL DEFAULT 0, 
            \`test_mode\` tinyint NOT NULL DEFAULT 0, 
            PRIMARY KEY (\`id\`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`);

        // 4. Tüm tabloların ID'lerini işle
        const tables = [
            'users', 'bank_accounts', 'companies', 'customers', 'payments', 
            'contract_monthly_prices', 'contract_staff', 'contracts', 'items', 
            'rooms', 'warehouses', 'transportation_job_staff', 'transportation_jobs', 
            'notifications', 'company_paytr_settings', 'company_mail_settings', 'company_sms_settings'
        ];

        for (const table of tables) {
            await processTableIds(table);
        }

        // 5. Referans Kolonlarını (Foreign Key) uyuştur
        const refColumns = [
            {table: 'users', column: 'company_id', nullable: true},
            {table: 'bank_accounts', column: 'company_id', nullable: false},
            {table: 'customers', column: 'user_id', nullable: true},
            {table: 'customers', column: 'company_id', nullable: false},
            {table: 'payments', column: 'contract_id', nullable: false},
            {table: 'payments', column: 'bank_account_id', nullable: true},
            {table: 'contract_monthly_prices', column: 'contract_id', nullable: false},
            {table: 'contract_staff', column: 'contract_id', nullable: false},
            {table: 'contract_staff', column: 'user_id', nullable: false},
            {table: 'contracts', column: 'customer_id', nullable: false},
            {table: 'contracts', column: 'room_id', nullable: false},
            {table: 'contracts', column: 'sold_by_user_id', nullable: true},
            {table: 'items', column: 'room_id', nullable: false},
            {table: 'items', column: 'contract_id', nullable: true},
            {table: 'rooms', column: 'warehouse_id', nullable: false},
            {table: 'warehouses', column: 'company_id', nullable: false},
            {table: 'transportation_job_staff', column: 'transportation_job_id', nullable: false},
            {table: 'transportation_job_staff', column: 'user_id', nullable: false},
            {table: 'transportation_jobs', column: 'company_id', nullable: false},
            {table: 'transportation_jobs', column: 'customer_id', nullable: false},
            {table: 'notifications', column: 'user_id', nullable: true},
            {table: 'notifications', column: 'customer_id', nullable: true},
            {table: 'company_paytr_settings', column: 'company_id', nullable: false},
            {table: 'company_mail_settings', column: 'company_id', nullable: false},
            {table: 'company_sms_settings', column: 'company_id', nullable: false}
        ];

        for (const ref of refColumns) {
            await safeModifyColumn(ref.table, ref.column, UUID_TYPE, ref.nullable);
        }

        // 6. Kısıtlamaları (Foreign Key) tekrar ekle
        const constraints = [
            {table: 'users', name: 'FK_7ae6334059289559722437bcc1c', query: `ALTER TABLE \`users\` ADD CONSTRAINT \`FK_7ae6334059289559722437bcc1c\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`)`},
            {table: 'bank_accounts', name: 'FK_869d5463de72be0afa52f0859e8', query: `ALTER TABLE \`bank_accounts\` ADD CONSTRAINT \`FK_869d5463de72be0afa52f0859e8\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`)`},
            {table: 'customers', name: 'FK_11d81cd7be87b6f8865b0cf7661', query: `ALTER TABLE \`customers\` ADD CONSTRAINT \`FK_11d81cd7be87b6f8865b0cf7661\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`)`},
            {table: 'payments', name: 'FK_52fc2356fb8c211c93d4b1496f3', query: `ALTER TABLE \`payments\` ADD CONSTRAINT \`FK_52fc2356fb8c211c93d4b1496f3\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`)`},
            {table: 'payments', name: 'FK_00ec82cb228fb85ddbe768fb6d4', query: `ALTER TABLE \`payments\` ADD CONSTRAINT \`FK_00ec82cb228fb85ddbe768fb6d4\` FOREIGN KEY (\`bank_account_id\`) REFERENCES \`bank_accounts\`(\`id\`)`},
            {table: 'contract_monthly_prices', name: 'FK_f50cfcdf9a2786b227ad3db00c6', query: `ALTER TABLE \`contract_monthly_prices\` ADD CONSTRAINT \`FK_f50cfcdf9a2786b227ad3db00c6\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`)`},
            {table: 'contract_staff', name: 'FK_618a7585cbb2740668221784703', query: `ALTER TABLE \`contract_staff\` ADD CONSTRAINT \`FK_618a7585cbb2740668221784703\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`)`},
            {table: 'contract_staff', name: 'FK_99cc0fdc6261f9d0bcf13b65992', query: `ALTER TABLE \`contract_staff\` ADD CONSTRAINT \`FK_99cc0fdc6261f9d0bcf13b65992\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`)`},
            {table: 'contracts', name: 'FK_2e66f7950711366031e3200413d', query: `ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_2e66f7950711366031e3200413d\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`)`},
            {table: 'contracts', name: 'FK_36b16ffbd8846bd80f7ae72241d', query: `ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_36b16ffbd8846bd80f7ae72241d\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\`(\`id\`)`},
            {table: 'contracts', name: 'FK_5dd5738dac15ca2687138a947fb', query: `ALTER TABLE \`contracts\` ADD CONSTRAINT \`FK_5dd5738dac15ca2687138a947fb\` FOREIGN KEY (\`sold_by_user_id\`) REFERENCES \`users\`(\`id\`)`},
            {table: 'items', name: 'FK_e4b5876b9dc744b77ecfb7f0eef', query: `ALTER TABLE \`items\` ADD CONSTRAINT \`FK_e4b5876b9dc744b77ecfb7f0eef\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\`(\`id\`)`},
            {table: 'items', name: 'FK_f64f3ab93032cbb4596c541d5ac', query: `ALTER TABLE \`items\` ADD CONSTRAINT \`FK_f64f3ab93032cbb4596c541d5ac\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\`(\`id\`)`},
            {table: 'rooms', name: 'FK_b1fd3fe7b80a5176ce8d8659d3f', query: `ALTER TABLE \`rooms\` ADD CONSTRAINT \`FK_b1fd3fe7b80a5176ce8d8659d3f\` FOREIGN KEY (\`warehouse_id\`) REFERENCES \`warehouses\`(\`id\`)`},
            {table: 'warehouses', name: 'FK_3fcbfd5832b46945f514a7d1f56', query: `ALTER TABLE \`warehouses\` ADD CONSTRAINT \`FK_3fcbfd5832b46945f514a7d1f56\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`)`},
            {table: 'transportation_job_staff', name: 'FK_f090ca206307005520fa973a860', query: `ALTER TABLE \`transportation_job_staff\` ADD CONSTRAINT \`FK_f090ca206307005520fa973a860\` FOREIGN KEY (\`transportation_job_id\`) REFERENCES \`transportation_jobs\`(\`id\`)`},
            {table: 'transportation_job_staff', name: 'FK_b59b22e5da96680bc7d7ed8a522', query: `ALTER TABLE \`transportation_job_staff\` ADD CONSTRAINT \`FK_b59b22e5da96680bc7d7ed8a522\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`)`},
            {table: 'transportation_jobs', name: 'FK_5bd54300645fa99e0b115c7362f', query: `ALTER TABLE \`transportation_jobs\` ADD CONSTRAINT \`FK_5bd54300645fa99e0b115c7362f\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`)`},
            {table: 'notifications', name: 'FK_9a8a82462cab47c73d25f49261f', query: `ALTER TABLE \`notifications\` ADD CONSTRAINT \`FK_9a8a82462cab47c73d25f49261f\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\`(\`id\`)`},
            {table: 'notifications', name: 'FK_b55350bc786b052e8523f313b9a', query: `ALTER TABLE \`notifications\` ADD CONSTRAINT \`FK_b55350bc786b052e8523f313b9a\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\`(\`id\`)`},
            {table: 'company_sms_settings', name: 'FK_d2b08509989c8a9869b417f48f1', query: `ALTER TABLE \`company_sms_settings\` ADD CONSTRAINT \`FK_d2b08509989c8a9869b417f48f1\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\`(\`id\`)`}
        ];

        for (const c of constraints) {
            await safeAddConstraint(c.table, c.name, c.query);
        }

        await queryRunner.query(`SET FOREIGN_KEY_CHECKS = 1`);
    }

    public async down(queryRunner: QueryRunner): Promise<void> {
    }
}
