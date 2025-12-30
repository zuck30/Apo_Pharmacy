DROP DATABASE IF EXISTS `Apotek_Pharmacy_db`;
USE `Apotek_Pharmacy_db`;

-- Combine customers and patients into a unified entity
DROP TABLE IF EXISTS `person`;
CREATE TABLE `person` (
  `person_id` INT NOT NULL AUTO_INCREMENT,
  `person_type` ENUM('CUSTOMER', 'PATIENT', 'EMPLOYEE', 'SUPPLIER_CONTACT') NOT NULL,
  `registration_number` VARCHAR(50) UNIQUE,
  `title` VARCHAR(10),
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100),
  `last_name` VARCHAR(100) NOT NULL,
  `date_of_birth` DATE,
  `gender` ENUM('MALE', 'FEMALE', 'OTHER'),
  `email` VARCHAR(100),
  `mobile` VARCHAR(20),
  `telephone` VARCHAR(20),
  `address` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` ENUM('ACTIVE', 'INACTIVE', 'SUSPENDED') DEFAULT 'ACTIVE',
  PRIMARY KEY (`person_id`),
  INDEX `idx_person_type` (`person_type`),
  INDEX `idx_registration` (`registration_number`),
  INDEX `idx_name` (`first_name`, `last_name`)
);

-- Customer-specific data (if needed)
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  `customer_id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT NOT NULL,
  `credit_limit` DECIMAL(20,2) DEFAULT 0.00,
  `total_credit` DECIMAL(20,2) DEFAULT 0.00,
  `opening_balance` DECIMAL(20,2) DEFAULT 0.00,
  `price_category_id` INT,
  PRIMARY KEY (`customer_id`),
  FOREIGN KEY (`person_id`) REFERENCES `person`(`person_id`) ON DELETE CASCADE,
  FOREIGN KEY (`price_category_id`) REFERENCES `price_category`(`ID`)
);

-- Patient-specific data
DROP TABLE IF EXISTS `patient`;
CREATE TABLE `patient` (
  `patient_id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT NOT NULL,
  `insurance_company` VARCHAR(100),
  `insurance_plan` VARCHAR(100),
  `membership_number` VARCHAR(100),
  `company` VARCHAR(100),
  `issue_date` DATE,
  `price_category_id` INT NOT NULL,
  PRIMARY KEY (`patient_id`),
  FOREIGN KEY (`person_id`) REFERENCES `person`(`person_id`) ON DELETE CASCADE,
  FOREIGN KEY (`price_category_id`) REFERENCES `price_category`(`ID`)
);

-- Create unified product table
DROP TABLE IF EXISTS `product`;
CREATE TABLE `product` (
  `product_id` INT NOT NULL AUTO_INCREMENT,
  `product_code` VARCHAR(50) UNIQUE NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `generic_name` VARCHAR(200),
  `material_group_id` INT,
  `material_subgroup_id` INT,
  `material_form_id` INT,
  `unit_id` INT,
  `quantity_per_unit` DECIMAL(10,2),
  `strength` VARCHAR(100),
  `dosage` VARCHAR(100),
  `indication` TEXT,
  `manufacturer_id` INT,
  `barcode` VARCHAR(100),
  `status` ENUM('ACTIVE', 'INACTIVE', 'DISCONTINUED') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  FOREIGN KEY (`material_group_id`) REFERENCES `material_group`(`material_group_id`),
  FOREIGN KEY (`material_subgroup_id`) REFERENCES `material_subgroup`(`material_subgroup_id`),
  FOREIGN KEY (`material_form_id`) REFERENCES `material_form`(`material_form_id`),
  FOREIGN KEY (`unit_id`) REFERENCES `unit`(`unit_id`),
  FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturer`(`manufacturer_id`),
  INDEX `idx_product_code` (`product_code`),
  INDEX `idx_product_name` (`product_name`)
);

-- Normalize material groups
DROP TABLE IF EXISTS `material_group`;
CREATE TABLE `material_group` (
  `material_group_id` INT NOT NULL AUTO_INCREMENT,
  `group_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  PRIMARY KEY (`material_group_id`),
  UNIQUE KEY `uk_group_name` (`group_name`)
);

DROP TABLE IF EXISTS `material_subgroup`;
CREATE TABLE `material_subgroup` (
  `material_subgroup_id` INT NOT NULL AUTO_INCREMENT,
  `material_group_id` INT NOT NULL,
  `subgroup_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  PRIMARY KEY (`material_subgroup_id`),
  FOREIGN KEY (`material_group_id`) REFERENCES `material_group`(`material_group_id`),
  UNIQUE KEY `uk_subgroup_name` (`material_group_id`, `subgroup_name`)
);

DROP TABLE IF EXISTS `material_form`;
CREATE TABLE `material_form` (
  `material_form_id` INT NOT NULL AUTO_INCREMENT,
  `form_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  PRIMARY KEY (`material_form_id`),
  UNIQUE KEY `uk_form_name` (`form_name`)
);

DROP TABLE IF EXISTS `unit`;
CREATE TABLE `unit` (
  `unit_id` INT NOT NULL AUTO_INCREMENT,
  `unit_name` VARCHAR(50) NOT NULL,
  `unit_symbol` VARCHAR(10),
  `status` ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
  PRIMARY KEY (`unit_id`),
  UNIQUE KEY `uk_unit_name` (`unit_name`)
);

-- Stock batch table
DROP TABLE IF EXISTS `stock_batch`;
CREATE TABLE `stock_batch` (
  `batch_id` INT NOT NULL AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `batch_number` VARCHAR(100) NOT NULL,
  `manufacturing_date` DATE,
  `expiry_date` DATE NOT NULL,
  `unit_cost` DECIMAL(20,2) NOT NULL,
  `initial_quantity` DECIMAL(10,2) NOT NULL,
  `current_quantity` DECIMAL(10,2) NOT NULL,
  `barcode` VARCHAR(100),
  `store_id` INT NOT NULL,
  `shelf_number` VARCHAR(50),
  `rack_number` VARCHAR(50),
  `received_date` DATETIME NOT NULL,
  `supplier_id` INT,
  `invoice_number` VARCHAR(100),
  `grn_number` VARCHAR(100),
  `status` ENUM('ACTIVE', 'EXPIRED', 'DEPLETED', 'RETURNED') DEFAULT 'ACTIVE',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`),
  FOREIGN KEY (`product_id`) REFERENCES `product`(`product_id`),
  FOREIGN KEY (`store_id`) REFERENCES `store`(`store_id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`supplier_id`),
  UNIQUE KEY `uk_batch` (`product_id`, `batch_number`, `store_id`),
  INDEX `idx_expiry` (`expiry_date`),
  INDEX `idx_batch_number` (`batch_number`)
);

-- Stock movement tracking
DROP TABLE IF EXISTS `stock_movement`;
CREATE TABLE `stock_movement` (
  `movement_id` INT NOT NULL AUTO_INCREMENT,
  `batch_id` INT NOT NULL,
  `movement_type` ENUM('PURCHASE', 'SALE', 'RETURN', 'ADJUSTMENT', 'TRANSFER_IN', 'TRANSFER_OUT', 'ISSUE') NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_cost` DECIMAL(20,2),
  `selling_price` DECIMAL(20,2),
  `reference_number` VARCHAR(100),
  `source_store_id` INT,
  `destination_store_id` INT,
  `person_id` INT, -- customer/patient/employee
  `movement_date` DATETIME NOT NULL,
  `notes` TEXT,
  `created_by` INT, -- user_id
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  FOREIGN KEY (`batch_id`) REFERENCES `stock_batch`(`batch_id`),
  FOREIGN KEY (`source_store_id`) REFERENCES `store`(`store_id`),
  FOREIGN KEY (`destination_store_id`) REFERENCES `store`(`store_id`),
  FOREIGN KEY (`person_id`) REFERENCES `person`(`person_id`),
  FOREIGN KEY (`created_by`) REFERENCES `user`(`user_id`),
  INDEX `idx_movement_date` (`movement_date`),
  INDEX `idx_movement_type` (`movement_type`)
);

-- Sales transaction header
DROP TABLE IF EXISTS `sales_transaction`;
CREATE TABLE `sales_transaction` (
  `transaction_id` INT NOT NULL AUTO_INCREMENT,
  `transaction_number` VARCHAR(100) UNIQUE NOT NULL,
  `transaction_type` ENUM('SALE', 'RETURN', 'QUOTE', 'ORDER') NOT NULL,
  `person_id` INT, -- customer/patient
  `store_id` INT NOT NULL,
  `counter_id` INT,
  `transaction_date` DATETIME NOT NULL,
  `subtotal` DECIMAL(20,2) NOT NULL,
  `discount_amount` DECIMAL(20,2) DEFAULT 0.00,
  `tax_amount` DECIMAL(20,2) DEFAULT 0.00,
  `total_amount` DECIMAL(20,2) NOT NULL,
  `payment_method` ENUM('CASH', 'CARD', 'MOBILE', 'CREDIT', 'INSURANCE'),
  `payment_status` ENUM('PENDING', 'PARTIAL', 'PAID', 'REFUNDED') DEFAULT 'PAID',
  `sale_type` ENUM('RETAIL', 'WHOLESALE', 'INSTITUTIONAL'),
  `status` ENUM('DRAFT', 'COMPLETED', 'CANCELLED', 'REFUNDED') DEFAULT 'COMPLETED',
  `notes` TEXT,
  `created_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  FOREIGN KEY (`person_id`) REFERENCES `person`(`person_id`),
  FOREIGN KEY (`store_id`) REFERENCES `store`(`store_id`),
  FOREIGN KEY (`created_by`) REFERENCES `user`(`user_id`),
  INDEX `idx_transaction_date` (`transaction_date`),
  INDEX `idx_transaction_number` (`transaction_number`)
);

-- Sales line items
DROP TABLE IF EXISTS `sales_line_item`;
CREATE TABLE `sales_line_item` (
  `line_item_id` INT NOT NULL AUTO_INCREMENT,
  `transaction_id` INT NOT NULL,
  `batch_id` INT NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `unit_cost` DECIMAL(20,2) NOT NULL,
  `selling_price` DECIMAL(20,2) NOT NULL,
  `line_discount` DECIMAL(20,2) DEFAULT 0.00,
  `line_total` DECIMAL(20,2) NOT NULL,
  `profit` DECIMAL(20,2) GENERATED ALWAYS AS ((`selling_price` - `unit_cost`) * `quantity`) STORED,
  `notes` VARCHAR(200),
  PRIMARY KEY (`line_item_id`),
  FOREIGN KEY (`transaction_id`) REFERENCES `sales_transaction`(`transaction_id`) ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`) REFERENCES `stock_batch`(`batch_id`),
  INDEX `idx_transaction` (`transaction_id`)
);

-- Purchase order
DROP TABLE IF EXISTS `purchase_order`;
CREATE TABLE `purchase_order` (
  `order_id` INT NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(100) UNIQUE NOT NULL,
  `supplier_id` INT NOT NULL,
  `order_date` DATETIME NOT NULL,
  `expected_delivery_date` DATE,
  `order_status` ENUM('DRAFT', 'PENDING', 'PARTIAL', 'RECEIVED', 'CANCELLED') DEFAULT 'DRAFT',
  `subtotal` DECIMAL(20,2),
  `tax_amount` DECIMAL(20,2) DEFAULT 0.00,
  `total_amount` DECIMAL(20,2),
  `notes` TEXT,
  `created_by` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`supplier_id`) REFERENCES `supplier`(`supplier_id`),
  FOREIGN KEY (`created_by`) REFERENCES `user`(`user_id`)
);

-- Purchase order line items
DROP TABLE IF EXISTS `purchase_order_item`;
CREATE TABLE `purchase_order_item` (
  `order_item_id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity_ordered` DECIMAL(10,2) NOT NULL,
  `quantity_received` DECIMAL(10,2) DEFAULT 0.00,
  `unit_price` DECIMAL(20,2) NOT NULL,
  `line_total` DECIMAL(20,2) GENERATED ALWAYS AS (`quantity_ordered` * `unit_price`) STORED,
  `item_status` ENUM('PENDING', 'PARTIAL', 'RECEIVED', 'CANCELLED') DEFAULT 'PENDING',
  `notes` VARCHAR(200),
  PRIMARY KEY (`order_item_id`),
  FOREIGN KEY (`order_id`) REFERENCES `purchase_order`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `product`(`product_id`)
);

DROP TABLE IF EXISTS `store`;
CREATE TABLE `store` (
  `store_id` INT NOT NULL AUTO_INCREMENT,
  `store_code` VARCHAR(50) UNIQUE NOT NULL,
  `store_name` VARCHAR(100) NOT NULL,
  `store_type` ENUM('MAIN', 'BRANCH', 'WAREHOUSE', 'COUNTER') NOT NULL,
  `parent_store_id` INT,
  `address` TEXT,
  `phone` VARCHAR(20),
  `email` VARCHAR(100),
  `manager_id` INT, -- employee_id
  `is_active` BOOLEAN DEFAULT TRUE,
  `transaction_enabled` BOOLEAN DEFAULT TRUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`store_id`),
  FOREIGN KEY (`parent_store_id`) REFERENCES `store`(`store_id`),
  FOREIGN KEY (`manager_id`) REFERENCES `employee`(`employee_id`)
);

DROP TABLE IF EXISTS `counter`;
CREATE TABLE `counter` (
  `counter_id` INT NOT NULL AUTO_INCREMENT,
  `counter_code` VARCHAR(50) NOT NULL,
  `counter_name` VARCHAR(100) NOT NULL,
  `store_id` INT NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`counter_id`),
  FOREIGN KEY (`store_id`) REFERENCES `store`(`store_id`),
  UNIQUE KEY `uk_counter_code` (`store_id`, `counter_code`)
);

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` INT NOT NULL AUTO_INCREMENT,
  `person_id` INT NOT NULL,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100),
  `role_id` INT NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `is_locked` BOOLEAN DEFAULT FALSE,
  `failed_login_attempts` INT DEFAULT 0,
  `last_login` DATETIME,
  `password_changed_at` DATETIME,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`person_id`) REFERENCES `person`(`person_id`),
  FOREIGN KEY (`role_id`) REFERENCES `role`(`role_id`)
);

DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT,
  `is_system` BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (`role_id`)
);

DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission` (
  `permission_id` INT NOT NULL AUTO_INCREMENT,
  `permission_code` VARCHAR(100) UNIQUE NOT NULL,
  `permission_name` VARCHAR(100) NOT NULL,
  `module` VARCHAR(50),
  `description` TEXT,
  PRIMARY KEY (`permission_id`)
);

DROP TABLE IF EXISTS `role_permission`;
CREATE TABLE `role_permission` (
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `role`(`role_id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permission`(`permission_id`) ON DELETE CASCADE
);

DROP TABLE IF EXISTS `user_store_access`;
CREATE TABLE `user_store_access` (
  `user_id` INT NOT NULL,
  `store_id` INT NOT NULL,
  PRIMARY KEY (`user_id`, `store_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`store_id`) REFERENCES `store`(`store_id`) ON DELETE CASCADE
);
