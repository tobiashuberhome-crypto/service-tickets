-- Update for existing Service Ticket App databases.
-- Run once if the original schema was already imported.
-- mysql -u ticket_user -p ticket_app < database/sql/2026_06_19_update_storage_scan.sql

SET NAMES utf8mb4;
USE `ticket_app`;

ALTER TABLE `spare_parts`
    ADD COLUMN `storage_location_1` varchar(191) DEFAULT NULL AFTER `supplier_ref`,
    ADD COLUMN `storage_location_2` varchar(191) DEFAULT NULL AFTER `storage_location_1`;

CREATE TABLE `spare_part_stock_movements` (
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

ALTER TABLE `ticket_parts`
    ADD COLUMN `stock_movement_id` bigint unsigned DEFAULT NULL AFTER `dolibarr_order_line_id`,
    ADD KEY `ticket_parts_stock_movement_id_foreign` (`stock_movement_id`),
    ADD CONSTRAINT `ticket_parts_stock_movement_id_foreign`
        FOREIGN KEY (`stock_movement_id`) REFERENCES `spare_part_stock_movements` (`id`) ON DELETE SET NULL;

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_06_19_000010_add_storage_locations_to_spare_parts_table', 2),
    ('2026_06_19_000011_create_spare_part_stock_movements_table', 2),
    ('2026_06_19_000012_add_stock_movement_id_to_ticket_parts_table', 2)
ON DUPLICATE KEY UPDATE
    `batch` = VALUES(`batch`);
