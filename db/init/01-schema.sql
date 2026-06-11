-- ============================================================
-- 药品/医疗器械/化妆品 填报平台 - 数据库初始化
-- 编码: UTF-8 | 引擎: InnoDB | 排序: utf8mb4_unicode_ci
-- DB: diaocha / User: diaocha / Pass: diaocha
-- ============================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- -----------------------------------------------------------
-- 1. 系统配置表 (平台标题、须知、广告图等)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sys_config` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `config_key` VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
  `config_value` TEXT COMMENT '配置值',
  `config_type` ENUM('text','image','html','json') DEFAULT 'text',
  `page_level` TINYINT DEFAULT 0 COMMENT '所属页面级别 0=全局 1=首页 2=二级 3=三级 4=四级',
  `category_id` INT UNSIGNED DEFAULT NULL COMMENT '关联分类(二/三/四级页面)',
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置';

-- -----------------------------------------------------------
-- 2. 分类表 (药品/医疗器械/化妆品)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL COMMENT '分类名称',
  `icon` VARCHAR(255) DEFAULT NULL COMMENT '图标路径',
  `sort_order` INT DEFAULT 0,
  `status` TINYINT DEFAULT 1 COMMENT '1=启用 0=禁用',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='报告分类';

-- -----------------------------------------------------------
-- 3. 知识信息表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `knowledge` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED DEFAULT NULL COMMENT '所属分类(NULL=首页通用)',
  `title` VARCHAR(200) NOT NULL,
  `summary` VARCHAR(500) DEFAULT NULL,
  `content` TEXT,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `page_level` TINYINT DEFAULT 1 COMMENT '1=首页 2=二级页',
  `sort_order` INT DEFAULT 0,
  `status` TINYINT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='知识信息';

-- -----------------------------------------------------------
-- 4. 注意事项表 (三级页面)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL COMMENT '关联分类',
  `title` VARCHAR(200) DEFAULT '请您注意',
  `content` TEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='注意事项';

-- -----------------------------------------------------------
-- 5. 地区表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `regions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `parent_id` INT UNSIGNED DEFAULT NULL,
  `level` TINYINT DEFAULT 1 COMMENT '1=市 2=区',
  `sort_order` INT DEFAULT 0,
  `status` TINYINT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='地区';

-- -----------------------------------------------------------
-- 6. 管理员表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `real_name` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('super_admin','admin','viewer') DEFAULT 'admin',
  `permissions` JSON DEFAULT NULL COMMENT '功能权限列表',
  `region_ids` JSON DEFAULT NULL COMMENT '可管理地区ID列表',
  `status` TINYINT DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员';

-- -----------------------------------------------------------
-- 7. 动态表单字段定义表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `form_fields` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL COMMENT '关联分类',
  `field_name` VARCHAR(100) NOT NULL COMMENT '字段标签名称',
  `field_type` ENUM('text','textarea','date','image','radio','region') NOT NULL,
  `field_options` JSON DEFAULT NULL COMMENT '选项配置(radio选项/textarea大小等)',
  `is_required` TINYINT DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `status` TINYINT DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='动态表单字段定义';

-- -----------------------------------------------------------
-- 8. 填报记录表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `region_id` INT UNSIGNED DEFAULT NULL,
  `form_data` JSON NOT NULL COMMENT '提交的表单数据',
  `status` ENUM('draft','submitted','reviewed') DEFAULT 'submitted',
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `reviewer_id` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='填报记录';

-- -----------------------------------------------------------
-- 9. 上传文件记录表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `uploads` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='上传文件';
