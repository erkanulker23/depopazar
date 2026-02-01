import { MigrationInterface, QueryRunner } from 'typeorm';

export class InitialSchema1700000000000 implements MigrationInterface {
  public async up(queryRunner: QueryRunner): Promise<void> {
    // Companies table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`companies\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`name\` varchar(255) NOT NULL,
        \`slug\` varchar(255) NOT NULL,
        \`logo_url\` varchar(255) DEFAULT NULL,
        \`primary_color\` varchar(100) DEFAULT NULL,
        \`secondary_color\` varchar(100) DEFAULT NULL,
        \`email\` varchar(255) DEFAULT NULL,
        \`phone\` varchar(20) DEFAULT NULL,
        \`address\` text DEFAULT NULL,
        \`package_type\` varchar(50) NOT NULL DEFAULT 'basic',
        \`max_warehouses\` int NOT NULL DEFAULT 0,
        \`max_rooms\` int NOT NULL DEFAULT 0,
        \`max_customers\` int NOT NULL DEFAULT 0,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`subscription_expires_at\` datetime DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`UQ_companies_slug\` (\`slug\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Users table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`users\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`email\` varchar(255) NOT NULL,
        \`password\` varchar(255) NOT NULL,
        \`first_name\` varchar(100) NOT NULL,
        \`last_name\` varchar(100) NOT NULL,
        \`phone\` varchar(20) DEFAULT NULL,
        \`role\` varchar(50) NOT NULL DEFAULT 'customer',
        \`company_id\` char(36) DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`last_login_at\` datetime DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`UQ_users_email\` (\`email\`),
        KEY \`IDX_users_company\` (\`company_id\`),
        CONSTRAINT \`FK_users_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Warehouses table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`warehouses\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`name\` varchar(255) NOT NULL,
        \`company_id\` char(36) NOT NULL,
        \`address\` text DEFAULT NULL,
        \`city\` varchar(100) DEFAULT NULL,
        \`district\` varchar(100) DEFAULT NULL,
        \`total_floors\` int DEFAULT NULL,
        \`description\` text DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_warehouses_company\` (\`company_id\`),
        CONSTRAINT \`FK_warehouses_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Rooms table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`rooms\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`room_number\` varchar(100) NOT NULL,
        \`warehouse_id\` char(36) NOT NULL,
        \`area_m2\` decimal(10,2) NOT NULL,
        \`monthly_price\` decimal(10,2) NOT NULL,
        \`status\` varchar(50) NOT NULL DEFAULT 'empty',
        \`floor\` varchar(50) DEFAULT NULL,
        \`block\` varchar(50) DEFAULT NULL,
        \`corridor\` varchar(50) DEFAULT NULL,
        \`description\` text DEFAULT NULL,
        \`notes\` text DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_rooms_warehouse\` (\`warehouse_id\`),
        CONSTRAINT \`FK_rooms_warehouse\` FOREIGN KEY (\`warehouse_id\`) REFERENCES \`warehouses\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Customers table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`customers\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`user_id\` char(36) DEFAULT NULL,
        \`company_id\` char(36) NOT NULL,
        \`first_name\` varchar(100) NOT NULL,
        \`last_name\` varchar(100) NOT NULL,
        \`email\` varchar(255) NOT NULL,
        \`phone\` varchar(20) DEFAULT NULL,
        \`identity_number\` varchar(20) DEFAULT NULL,
        \`address\` text DEFAULT NULL,
        \`notes\` text DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_customers_company\` (\`company_id\`),
        KEY \`IDX_customers_user\` (\`user_id\`),
        CONSTRAINT \`FK_customers_user\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\` (\`id\`) ON DELETE SET NULL,
        CONSTRAINT \`FK_customers_company\` FOREIGN KEY (\`company_id\`) REFERENCES \`companies\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Contracts table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`contracts\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`contract_number\` varchar(100) NOT NULL,
        \`customer_id\` char(36) NOT NULL,
        \`room_id\` char(36) NOT NULL,
        \`start_date\` datetime NOT NULL,
        \`end_date\` datetime NOT NULL,
        \`monthly_price\` decimal(10,2) NOT NULL,
        \`payment_frequency_months\` int NOT NULL DEFAULT 1,
        \`terms\` text DEFAULT NULL,
        \`notes\` text DEFAULT NULL,
        \`is_active\` tinyint(1) NOT NULL DEFAULT 1,
        \`terminated_at\` datetime DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`UQ_contracts_number\` (\`contract_number\`),
        KEY \`IDX_contracts_customer\` (\`customer_id\`),
        KEY \`IDX_contracts_room\` (\`room_id\`),
        CONSTRAINT \`FK_contracts_customer\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\` (\`id\`) ON DELETE CASCADE,
        CONSTRAINT \`FK_contracts_room\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Payments table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`payments\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`payment_number\` varchar(100) NOT NULL,
        \`contract_id\` char(36) NOT NULL,
        \`amount\` decimal(10,2) NOT NULL,
        \`status\` varchar(50) NOT NULL DEFAULT 'pending',
        \`due_date\` datetime NOT NULL,
        \`paid_at\` datetime DEFAULT NULL,
        \`payment_method\` varchar(100) DEFAULT NULL,
        \`transaction_id\` varchar(255) DEFAULT NULL,
        \`notes\` text DEFAULT NULL,
        \`days_overdue\` int NOT NULL DEFAULT 0,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        UNIQUE KEY \`UQ_payments_number\` (\`payment_number\`),
        KEY \`IDX_payments_contract\` (\`contract_id\`),
        CONSTRAINT \`FK_payments_contract\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Items table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`items\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`room_id\` char(36) NOT NULL,
        \`contract_id\` char(36) DEFAULT NULL,
        \`name\` varchar(255) NOT NULL,
        \`description\` text DEFAULT NULL,
        \`quantity\` int DEFAULT NULL,
        \`unit\` varchar(50) DEFAULT NULL,
        \`photo_url\` text DEFAULT NULL,
        \`stored_at\` datetime DEFAULT NULL,
        \`removed_at\` datetime DEFAULT NULL,
        \`notes\` text DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_items_room\` (\`room_id\`),
        KEY \`IDX_items_contract\` (\`contract_id\`),
        CONSTRAINT \`FK_items_room\` FOREIGN KEY (\`room_id\`) REFERENCES \`rooms\` (\`id\`) ON DELETE CASCADE,
        CONSTRAINT \`FK_items_contract\` FOREIGN KEY (\`contract_id\`) REFERENCES \`contracts\` (\`id\`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);

    // Notifications table
    await queryRunner.query(`
      CREATE TABLE IF NOT EXISTS \`notifications\` (
        \`id\` char(36) NOT NULL DEFAULT (UUID()),
        \`user_id\` char(36) DEFAULT NULL,
        \`customer_id\` char(36) DEFAULT NULL,
        \`type\` varchar(50) NOT NULL,
        \`title\` varchar(255) NOT NULL,
        \`message\` text NOT NULL,
        \`is_read\` tinyint(1) NOT NULL DEFAULT 0,
        \`read_at\` datetime DEFAULT NULL,
        \`metadata\` json DEFAULT NULL,
        \`created_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        \`deleted_at\` datetime DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`IDX_notifications_user\` (\`user_id\`),
        KEY \`IDX_notifications_customer\` (\`customer_id\`),
        CONSTRAINT \`FK_notifications_user\` FOREIGN KEY (\`user_id\`) REFERENCES \`users\` (\`id\`) ON DELETE CASCADE,
        CONSTRAINT \`FK_notifications_customer\` FOREIGN KEY (\`customer_id\`) REFERENCES \`customers\` (\`id\`) ON DELETE CASCADE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    `);
  }

  public async down(queryRunner: QueryRunner): Promise<void> {
    await queryRunner.query(`DROP TABLE IF EXISTS \`notifications\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`items\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`payments\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`contracts\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`customers\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`rooms\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`warehouses\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`users\``);
    await queryRunner.query(`DROP TABLE IF EXISTS \`companies\``);
  }
}
