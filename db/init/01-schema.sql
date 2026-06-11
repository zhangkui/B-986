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
  `query_code` VARCHAR(32) NOT NULL UNIQUE COMMENT '查询码，用于移动端跟踪',
  `category_id` INT UNSIGNED NOT NULL,
  `region_id` INT UNSIGNED DEFAULT NULL,
  `form_data` JSON NOT NULL COMMENT '提交的表单数据',
  `supplement_data` JSON DEFAULT NULL COMMENT '补充材料数据',
  `status` ENUM('pending','processing','supplement','completed','rejected') DEFAULT 'pending' COMMENT '待受理/处理中/待补充/已办结/已驳回',
  `handler_id` INT UNSIGNED DEFAULT NULL COMMENT '当前处理人ID',
  `handle_opinion` TEXT COMMENT '处理意见',
  `handle_result` TEXT COMMENT '办结结果',
  `handle_attachments` JSON DEFAULT NULL COMMENT '处理附件URL列表',
  `supplement_request` TEXT COMMENT '要求补充材料的说明',
  `admin_remark` TEXT COMMENT '后台备注',
  `submitted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `accepted_at` DATETIME DEFAULT NULL COMMENT '受理时间',
  `handled_at` DATETIME DEFAULT NULL COMMENT '处理完成时间',
  `reviewer_id` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`handler_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
  KEY `idx_status` (`status`),
  KEY `idx_query_code` (`query_code`),
  KEY `idx_handler` (`handler_id`),
  KEY `idx_created_at` (`created_at`)
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

-- -----------------------------------------------------------
-- 10. 举报工单流转日志表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `report_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `report_id` INT UNSIGNED NOT NULL COMMENT '关联举报ID',
  `action` VARCHAR(32) NOT NULL COMMENT '操作类型: submit/accept/assign/supplement_request/supplement_submit/complete/reject/remark',
  `operator_id` INT UNSIGNED DEFAULT NULL COMMENT '操作人ID (管理员或NULL表示用户)',
  `operator_type` ENUM('admin','user') DEFAULT 'admin' COMMENT '操作人类型',
  `operator_name` VARCHAR(100) DEFAULT NULL COMMENT '操作人名称快照',
  `remark` TEXT COMMENT '操作备注/意见',
  `from_status` VARCHAR(32) DEFAULT NULL COMMENT '操作前状态',
  `to_status` VARCHAR(32) DEFAULT NULL COMMENT '操作后状态',
  `extra_data` JSON DEFAULT NULL COMMENT '附加数据',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`report_id`) REFERENCES `reports`(`id`) ON DELETE CASCADE,
  KEY `idx_report_id` (`report_id`),
  KEY `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='举报工单流转日志';
