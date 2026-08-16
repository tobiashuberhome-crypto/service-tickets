-- Service Ticket App database schema
-- Target database: ticket_app
-- Import example:
-- mysql -u ticket_user -p ticket_app < ticket_app_schema.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

USE `ticket_app`;

CREATE TABLE IF NOT EXISTS `migrations` (
    `id` int unsigned NOT NULL AUTO_INCREMENT,
    `migration` varchar(191) NOT NULL,
    `batch` int NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `migrations_migration_unique` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `service_defaults` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `product_ref` varchar(191) NOT NULL,
    `label` varchar(191) DEFAULT NULL,
    `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `service_defaults_product_ref_unique` (`product_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spare_parts` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `part_ref` varchar(191) NOT NULL,
    `label` varchar(191) NOT NULL,
    `description` text DEFAULT NULL,
    `manufacturer` varchar(191) DEFAULT NULL,
    `supplier` varchar(191) DEFAULT NULL,
    `supplier_ref` varchar(191) DEFAULT NULL,
    `storage_location_1` varchar(191) DEFAULT NULL,
    `storage_location_2` varchar(191) DEFAULT NULL,
    `purchase_price` decimal(12,2) DEFAULT NULL,
    `sales_price` decimal(12,2) NOT NULL DEFAULT 0.00,
    `vat_rate` decimal(5,2) NOT NULL DEFAULT 19.00,
    `unit` varchar(20) NOT NULL DEFAULT 'Stk',
    `stock_quantity` decimal(12,3) NOT NULL DEFAULT 0.000,
    `minimum_stock` decimal(12,3) DEFAULT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `spare_parts_part_ref_unique` (`part_ref`),
    KEY `spare_parts_manufacturer_index` (`manufacturer`),
    KEY `spare_parts_active_index` (`active`),
    KEY `spare_parts_part_ref_label_index` (`part_ref`, `label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machine_spare_part_compatibilities` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `machine_product_id` bigint unsigned NOT NULL,
    `spare_part_id` bigint unsigned NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `machine_part_unique` (`machine_product_id`, `spare_part_id`),
    KEY `machine_spare_part_compatibilities_machine_product_id_index` (`machine_product_id`),
    KEY `machine_spare_part_compatibilities_spare_part_id_foreign` (`spare_part_id`),
    CONSTRAINT `machine_spare_part_compatibilities_spare_part_id_foreign`
        FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_machines` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `dolibarr_customer_id` bigint unsigned NOT NULL,
    `customer_name_snapshot` varchar(191) NOT NULL,
    `dolibarr_machine_product_id` bigint unsigned NOT NULL,
    `manufacturer_snapshot` varchar(191) DEFAULT NULL,
    `machine_ref_snapshot` varchar(191) NOT NULL,
    `serial_number` varchar(191) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `customer_machines_dolibarr_customer_id_index` (`dolibarr_customer_id`),
    KEY `customer_machines_dolibarr_machine_product_id_index` (`dolibarr_machine_product_id`),
    KEY `customer_machines_serial_number_index` (`serial_number`),
    KEY `customer_machine_lookup` (`dolibarr_customer_id`, `dolibarr_machine_product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tickets` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `ticket_number` varchar(191) NOT NULL,
    `dolibarr_order_id` bigint unsigned DEFAULT NULL,
    `dolibarr_order_ref` varchar(191) DEFAULT NULL,
    `dolibarr_customer_id` bigint unsigned NOT NULL,
    `customer_name_snapshot` varchar(191) NOT NULL,
    `customer_machine_id` bigint unsigned NOT NULL,
    `service_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `repair_enabled` tinyint(1) NOT NULL DEFAULT 0,
    `error_description` text DEFAULT NULL,
    `acceptance_date` date NOT NULL,
    `target_date` date DEFAULT NULL,
    `status` varchar(30) NOT NULL DEFAULT 'open',
    `sync_status` varchar(30) NOT NULL DEFAULT 'pending',
    `sync_message` text DEFAULT NULL,
    `completed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `tickets_ticket_number_unique` (`ticket_number`),
    KEY `tickets_dolibarr_order_id_index` (`dolibarr_order_id`),
    KEY `tickets_dolibarr_order_ref_index` (`dolibarr_order_ref`),
    KEY `tickets_dolibarr_customer_id_index` (`dolibarr_customer_id`),
    KEY `tickets_customer_machine_id_foreign` (`customer_machine_id`),
    KEY `tickets_status_index` (`status`),
    KEY `tickets_sync_status_index` (`sync_status`),
    CONSTRAINT `tickets_customer_machine_id_foreign`
        FOREIGN KEY (`customer_machine_id`) REFERENCES `customer_machines` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_service_lines` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `ticket_id` bigint unsigned NOT NULL,
    `service_default_id` bigint unsigned DEFAULT NULL,
    `product_ref` varchar(191) NOT NULL,
    `label_snapshot` varchar(191) NOT NULL,
    `quantity` decimal(10,2) NOT NULL DEFAULT 1.00,
    `sales_price_snapshot` decimal(12,2) DEFAULT NULL,
    `vat_rate_snapshot` decimal(5,2) NOT NULL DEFAULT 19.00,
    `dolibarr_order_line_id` bigint unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `ticket_service_ref_unique` (`ticket_id`, `product_ref`),
    KEY `ticket_service_lines_service_default_id_foreign` (`service_default_id`),
    KEY `ticket_service_lines_dolibarr_order_line_id_index` (`dolibarr_order_line_id`),
    CONSTRAINT `ticket_service_lines_ticket_id_foreign`
        FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `ticket_service_lines_service_default_id_foreign`
        FOREIGN KEY (`service_default_id`) REFERENCES `service_defaults` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `spare_part_stock_movements` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `spare_part_id` bigint unsigned NOT NULL,
    `ticket_id` bigint unsigned DEFAULT NULL,
    `type` varchar(40) NOT NULL,
    `quantity` decimal(12,3) NOT NULL,
    `stock_before` decimal(12,3) NOT NULL,
    `stock_after` decimal(12,3) NOT NULL,
    `code_snapshot` varchar(191) DEFAULT NULL,
    `note` varchar(191) DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `spare_part_stock_movements_spare_part_id_foreign` (`spare_part_id`),
    KEY `spare_part_stock_movements_ticket_id_foreign` (`ticket_id`),
    KEY `spare_part_stock_movements_type_index` (`type`),
    CONSTRAINT `spare_part_stock_movements_spare_part_id_foreign`
        FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE CASCADE,
    CONSTRAINT `spare_part_stock_movements_ticket_id_foreign`
        FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ticket_parts` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `ticket_id` bigint unsigned NOT NULL,
    `spare_part_id` bigint unsigned DEFAULT NULL,
    `quantity` decimal(12,3) NOT NULL,
    `part_ref_snapshot` varchar(191) NOT NULL,
    `label_snapshot` varchar(191) NOT NULL,
    `description_snapshot` text DEFAULT NULL,
    `purchase_price_snapshot` decimal(12,2) DEFAULT NULL,
    `sales_price_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
    `vat_rate_snapshot` decimal(5,2) NOT NULL DEFAULT 19.00,
    `unit_snapshot` varchar(20) NOT NULL DEFAULT 'Stk',
    `dolibarr_order_line_id` bigint unsigned DEFAULT NULL,
    `stock_movement_id` bigint unsigned DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ticket_parts_ticket_id_foreign` (`ticket_id`),
    KEY `ticket_parts_spare_part_id_foreign` (`spare_part_id`),
    KEY `ticket_parts_dolibarr_order_line_id_index` (`dolibarr_order_line_id`),
    KEY `ticket_parts_stock_movement_id_foreign` (`stock_movement_id`),
    CONSTRAINT `ticket_parts_ticket_id_foreign`
        FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `ticket_parts_spare_part_id_foreign`
        FOREIGN KEY (`spare_part_id`) REFERENCES `spare_parts` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ticket_parts_stock_movement_id_foreign`
        FOREIGN KEY (`stock_movement_id`) REFERENCES `spare_part_stock_movements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `machine_documents` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `machine_product_id` bigint unsigned NOT NULL,
    `title` varchar(191) NOT NULL,
    `url` text NOT NULL,
    `active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `machine_documents_machine_product_id_index` (`machine_product_id`),
    KEY `machine_documents_active_index` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sync_logs` (
    `id` bigint unsigned NOT NULL AUTO_INCREMENT,
    `ticket_id` bigint unsigned DEFAULT NULL,
    `action` varchar(191) NOT NULL,
    `status` varchar(30) NOT NULL,
    `message` text DEFAULT NULL,
    `payload` json DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `sync_logs_ticket_id_foreign` (`ticket_id`),
    KEY `sync_logs_action_index` (`action`),
    KEY `sync_logs_status_index` (`status`),
    CONSTRAINT `sync_logs_ticket_id_foreign`
        FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `service_defaults`
    (`product_ref`, `label`, `quantity`, `active`, `created_at`, `updated_at`)
VALUES
    ('NM-Klein', 'NM-Klein', 1.00, 1, NOW(), NOW()),
    ('NM-Service', 'NM-Service', 1.00, 1, NOW(), NOW()),
    ('VDE', 'VDE', 1.00, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `label` = VALUES(`label`),
    `quantity` = VALUES(`quantity`),
    `active` = VALUES(`active`),
    `updated_at` = NOW();

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_06_18_000001_create_service_defaults_table', 1),
    ('2026_06_18_000002_create_spare_parts_table', 1),
    ('2026_06_18_000003_create_machine_spare_part_compatibilities_table', 1),
    ('2026_06_18_000004_create_customer_machines_table', 1),
    ('2026_06_18_000005_create_tickets_table', 1),
    ('2026_06_18_000006_create_ticket_service_lines_table', 1),
    ('2026_06_18_000007_create_ticket_parts_table', 1),
    ('2026_06_18_000008_create_machine_documents_table', 1),
    ('2026_06_18_000009_create_sync_logs_table', 1),
    ('2026_06_19_000010_add_storage_locations_to_spare_parts_table', 1),
    ('2026_06_19_000011_create_spare_part_stock_movements_table', 1),
    ('2026_06_19_000012_add_stock_movement_id_to_ticket_parts_table', 1)
ON DUPLICATE KEY UPDATE
    `batch` = VALUES(`batch`);

SET FOREIGN_KEY_CHECKS = 1;
