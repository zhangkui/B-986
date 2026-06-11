# 药品医疗器械化妆品不良反应/事件填报平台

## 🧭 项目类型
- 类型：**A) 全栈 Web 应用**

## 🧩 技术栈
- Frontend: Vue 3 + Vite + Vant (移动端H5) + Element Plus (管理后台)
- Backend: PHP 8.3 (纯 PHP REST API, Apache)
- Database: MySQL 8.0
- Cache: Redis 7 (可选)
- Proxy: Nginx (前端生产环境)

## 如何运行

1. 确保 Docker Desktop / Docker Engine 正在运行
2. 在项目根目录运行 `docker compose up`
3. 等待约 1-2 分钟完成首次构建
4. 访问：
   - 移动端 H5：http://localhost:3101
   - 管理后台：http://localhost:3101/admin/login
   - API 接口：http://localhost:3102/api/health

## 服务列表

| 服务 | 地址 | 端口映射 |
|------|------|----------|
| 前端服务 (Nginx) | http://localhost:3101 | 3101 → 80 |
| 后端服务 (PHP/Apache) | http://localhost:3102 | 3102 → 80 |
| 数据库服务 (MySQL) | localhost:3103 | 3103 → 3306 |
| 缓存服务 (Redis) | localhost:3004 | 3004 → 6379 |

## 🔗 服务地址（备用列表）
| 服务 | 地址 | 端口 |
|------|------|------|
| 前端服务 (Nginx) | http://localhost:3101 | 3101 → 80 |
| 后端服务 (PHP/Apache) | http://localhost:3102 | 3102 → 80 |
| 数据库服务 (MySQL) | localhost:3103 | 3103 → 3306 |
| 缓存服务 (Redis) | localhost:3004 | 3004 → 6379 |

> 运行槽位：1 | 项目名称：label-986-slot1  
> 端口配置：3101（前端）/ 3102（后端）/ 3103（数据库）/ 3004（Redis）

## 👤 测试账号
测试账号信息请查看本节（仅写在 README，不在登录页展示）：

| 用户名 | 密码 | 角色 | 权限 |
|--------|------|------|------|
| admin | admin123 | 超级管理员 | 全部功能 + 全部地区 |
| hefei_admin | admin123 | 合肥管理员 | 内容/表单/报表 + 合肥主城区 |
| yaohai_admin | admin123 | 瑶海区管理员 | 表单/报表 + 瑶海区 |

## 📦 数据库凭据
- 用户名：diaocha
- 数据库名：diaocha
- 密码：diaocha

## ✅ 功能清单（对齐 prompt.md）
- [x] F1：首页 - 标题/广告图/知识列表/须知弹窗 (R002-R006)
- [x] F2：二级页 - 标签菜单/分类知识/立即报告 (R007-R010)
- [x] F3：三级页 - 注意事项/返回/填写报告 (R011-R012)
- [x] F4：四级页 - 动态表单/地区选择/提交 (R013-R015, R017, R018)
- [x] F5：后台内容管理 - CRUD (R016)
- [x] F6：后台表单配置 - 动态字段管理 (R017)
- [x] F7：管理员管理 - RBAC + 地区权限 (R019)
- [x] F8：报表功能 - 按地区筛选/导出 (R020)
- [x] F9：全局完善 - 错误处理/中文编码 (R021)

## 验证说明

### 成功路径
1. `docker compose up` 启动全部 4 个服务
2. 访问 http://localhost:3101 - 移动端首页加载成功
3. 点击"我要报告" - 报告入口页显示 3 个标签
4. 选择分类 → 注意事项页 → 表单页
5. 访问 http://localhost:3101/admin/login - 管理员登录
6. 使用 admin/admin123 登录 - 查看统计仪表盘

### 失败路径
1. 未认证访问 API → 返回 401
2. 无权限执行管理操作 → 返回 403
3. 提交空表单 → 返回 400 错误信息

### API 测试
- `GET /api/health` → `{"status":"ok"}`
- `POST /api/admin/login` 使用有效凭证 → 返回 JWT 令牌
- `GET /api/admin/reports` 未认证访问 → 返回 401

## 🔎 自测说明
### 成功路径
1. `docker compose up` 启动全部服务
2. 访问 http://localhost:3101 查看移动端首页
3. 点击"我要报告"查看二级页面
4. 选择分类进入三级页面查看注意事项
5. 点击"填写报告"进入四级表单页面
6. 访问 http://localhost:3101/admin/login 进入管理后台

### 失败路径
1. 未登录访问管理 API → 返回 401
2. 无权限访问管理功能 → 返回 403
3. 提交空表单 → 返回 400 错误提示

### 边界/异常
- 端口冲突：修改 docker-compose.yml 中的端口映射
- 数据库初始化失败：检查 db/init/ 下 SQL 文件编码

## 🧾 证据文件
- evidence/run-slot1/TEST/（本轮 QC-FIX 证据目录）
- evidence/qa/（六维证据与文本树）

### R10 最新证据（Slot 1）
- `evidence/run-slot1/TEST/slot1-r10-round-consistency.txt`：轮次一致性检查报告
- `evidence/run-slot1/TEST/slot1-q3-home-banner.png`：首页 Banner 实际效果（真实场景图）
- `evidence/run-slot1/TEST/slot1-q3-level2-banner.png`：二级页 Banner 实际效果（真实场景图）
- `evidence/run-slot1/TEST/slot1-q3-level3-banner.png`：三级页 Banner 实际效果（真实场景图）
- `evidence/run-slot1/TEST/slot1-q3-image-map-before-after.txt`：图片映射盘点与审计
- `evidence/run-slot1/TEST/slot1-q3-image-health.txt`：图片资源健康检查
- `evidence/run-slot1/TEST/slot1-q3-image-source-log.txt`：图片来源与授权记录
- `evidence/run-slot1/TEST/slot1-q3-evidence-integrity.txt`：证据完整性检查

### R6-R9 历史证据
- `evidence/run-slot1/TEST/slot1-r6-regression.txt`：服务与 API 验证结果
- `evidence/run-slot1/TEST/slot1-r6-credentials-check.txt`：账密一致性校验
- `evidence/run-slot1/TEST/slot1-q2-region-api.json`：地区 API 响应（Q2 验证）
- `evidence/run-slot1/TEST/slot1-q1-notice-before-after.txt`：须知内容验证（Q1 验证）

### Slot 0 历史证据（参考）
- `evidence/run-slot0/`：历史截图与验证记录
- `evidence/qa/slot0-*`：历史六维证据

## 📋 当前阶段
本项目已完成 R10 轮次修复，解决 Q1/Q2/Q3 三个质检问题：
- **Q1（平台须知繁体字）**：已验证，内容为简体中文
- **Q2（地区选择为空白）**：已修复，API 正常返回合肥市及 12 个区县
- **Q3（图片与 Prompt 要求不符）**：已修复，8 张真实场景图片已部署，3 个 PNG 页面截图证据已生成

### 关键文档
- `QA_REPORT.md` - 完整审计报告（R1-R10）
- `SELF_CHECK.md` - 自检报告
- `cc-help.md` - 审计状态（CurrentRound: R10）
- `kimi-optimize.md` - 修复执行记录

### 当前状态
- **审计轮次**：R10（当前有效轮次）
- **修复状态**：DONE ✅
- **证据归档**：`evidence/run-slot1/TEST/`
