CREATE TABLE IF NOT EXISTS `PREFIX_tbpos_workstation` (
    `id_tbpos_workstation` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_tbpos_workstation`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_tbpos_token` (
    `id_tbpos_token` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_employee` INT(11) UNSIGNED NOT NULL,
    `value` CHAR(32) NOT NULL,
    `role` enum(ENUM_VALUES_ROLES) NOT NULL,
    `generated` INT(11) UNSIGNED NOT NULL,
    `expiration` INT(11) UNSIGNED NOT NULL,
    `id_tbpos_workstation` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `id_tbpos_order_process` INT(11) UNSIGNED NULL DEFAULT 0,
    PRIMARY KEY (`id_tbpos_token`),
    UNIQUE KEY `tbpos_t_value` (`value`),
    FOREIGN KEY `tbpos_t_employee` (`id_employee`) REFERENCES `PREFIX_employee`(`id_employee`) ON DELETE CASCADE
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_tbpos_employee_role` (
    `id_tbpos_employee_role` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_employee` INT(11) UNSIGNED NOT NULL,
    `role` ENUM(ENUM_VALUES_ROLES) NOT NULL,
    PRIMARY KEY (`id_tbpos_employee_role`),
    UNIQUE KEY `tbpos_er_role` (`id_employee`, `role`),
    FOREIGN KEY `tbpos_er_employee` (`id_employee`) REFERENCES `PREFIX_employee`(`id_employee`) ON DELETE CASCADE
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_tbpos_order_process` (
    `id_tbpos_order_process` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_tbpos_token` INT(11) UNSIGNED NOT NULL,
    `id_cart` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM(ENUM_VALUES_ORDER_PROCESS_STATUSES) NOT NULL,
    `payment_method` VARCHAR(40) NOT NULL DEFAULT '',
    PRIMARY KEY (`id_tbpos_order_process`),
    FOREIGN KEY `tbpos_op_token` (`id_tbpos_token`) REFERENCES `PREFIX_tbpos_token`(`id_tbpos_token`) ON DELETE RESTRICT
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;


-- DATA
INSERT INTO PREFIX_tbpos_employee_role(id_employee, role)
SELECT id_employee, 'CASHIER'
FROM PREFIX_employee;