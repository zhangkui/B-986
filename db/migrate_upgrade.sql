-- ============================================================
-- 数据库迁移脚本 - 举报管理系统升级
-- 适用于已有旧数据的数据库，执行此脚本升级
-- ============================================================

SET NAMES utf8mb4;

-- 1. 新增查询码字段 (先允许 NULL，后面再回填)
ALTER TABLE `reports` ADD COLUMN `query_code` VARCHAR(32) DEFAULT NULL COMMENT '查询码' AFTER `id`;

-- 2. 为已有记录生成查询码
UPDATE `reports` SET `query_code` = CONCAT('JB', DATE_FORMAT(created_at, '%Y%m%d'), LPAD(id, 4, '0')) WHERE `query_code` IS NULL;

-- 3. 回填后设置 NOT NULL 和 UNIQUE
ALTER TABLE `reports` MODIFY COLUMN `query_code` VARCHAR(32) NOT NULL UNIQUE COMMENT '查询码，用于移动端跟踪';

-- 4. 新增补充材料字段
ALTER TABLE `reports` ADD COLUMN `supplement_data` JSON DEFAULT NULL COMMENT '补充材料数据' AFTER `form_data`;

-- 5. 扩展状态枚举 (使用 ALTER TABLE ... MODIFY COLUMN)
-- MySQL 需要先转换已有旧状态值:
-- draft/submitted -> pending
-- reviewed -> processing (按实际业务映射)
UPDATE `reports` SET `status` = 'pending' WHERE `status` IN ('draft', 'submitted');
UPDATE `reports` SET `status` = 'processing' WHERE `status` = 'reviewed';

ALTER TABLE `reports` MODIFY COLUMN `status` ENUM('pending','processing','supplement','completed','rejected') DEFAULT 'pending' COMMENT '待受理/处理中/待补充/已办结/已驳回';

-- 6. 新增处理相关字段
ALTER TABLE `reports`
  ADD COLUMN `handler_id` INT UNSIGNED DEFAULT NULL COMMENT '当前处理人ID' AFTER `status`,
  ADD COLUMN `handle_opinion` TEXT COMMENT '处理意见' AFTER `handler_id`,
  ADD COLUMN `handle_result` TEXT COMMENT '办结结果' AFTER `handle_opinion`,
  ADD COLUMN `handle_attachments` JSON DEFAULT NULL COMMENT '处理附件URL列表' AFTER `handle_result`,
  ADD COLUMN `supplement_request` TEXT COMMENT '要求补充材料的说明' AFTER `handle_attachments`,
  ADD COLUMN `admin_remark` TEXT COMMENT '后台备注' AFTER `supplement_request`,
  ADD COLUMN `accepted_at` DATETIME DEFAULT NULL COMMENT '受理时间' AFTER `submitted_at`,
  ADD COLUMN `handled_at` DATETIME DEFAULT NULL COMMENT '处理完成时间' AFTER `accepted_at`;

-- 7. 添加索引
ALTER TABLE `reports`
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_query_code` (`query_code`),
  ADD KEY `idx_handler` (`handler_id`),
  ADD KEY `idx_created_at` (`created_at`);

-- 8. 添加外键
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_handler` FOREIGN KEY (`handler_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL;

-- 9. 创建流转日志表
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

-- 10. 为已有报告生成初始 submit 日志
INSERT INTO `report_logs` (`report_id`, `action`, `operator_id`, `operator_type`, `operator_name`, `remark`, `from_status`, `to_status`)
SELECT id, 'submit', NULL, 'user', '用户提交', NULL, NULL, 'pending' FROM reports;
