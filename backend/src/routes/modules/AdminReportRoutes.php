<?php
/**
 * 管理后台 - 举报工单管理路由 trait
 */
trait AdminReportRoutes
{
    private function handleAdminReports(string $method, string $uri, array $user): void
    {
        $db = Database::getConnection();

        // ---- 统计看板 ----
        if (preg_match('#^/api/admin/reports/stats$#', $uri) && $method === 'GET') {
            $this->handleAdminReportStats($user, $db);
            return;
        }

        // ---- 工单详情 ----
        if (preg_match('#^/api/admin/reports/(\d+)$#', $uri, $m) && $method === 'GET') {
            $this->handleAdminReportDetail((int)$m[1], $user, $db);
            return;
        }

        // ---- 受理工单 ----
        if (preg_match('#^/api/admin/reports/(\d+)/accept$#', $uri, $m) && $method === 'POST') {
            $this->handleReportAccept((int)$m[1], $user, $db);
            return;
        }

        // ---- 转派工单 ----
        if (preg_match('#^/api/admin/reports/(\d+)/assign$#', $uri, $m) && $method === 'POST') {
            $this->handleReportAssign((int)$m[1], $user, $db);
            return;
        }

        // ---- 要求补充材料 ----
        if (preg_match('#^/api/admin/reports/(\d+)/supplement-request$#', $uri, $m) && $method === 'POST') {
            $this->handleReportSupplementRequest((int)$m[1], $user, $db);
            return;
        }

        // ---- 办结工单 ----
        if (preg_match('#^/api/admin/reports/(\d+)/complete$#', $uri, $m) && $method === 'POST') {
            $this->handleReportComplete((int)$m[1], $user, $db);
            return;
        }

        // ---- 驳回工单 ----
        if (preg_match('#^/api/admin/reports/(\d+)/reject$#', $uri, $m) && $method === 'POST') {
            $this->handleReportReject((int)$m[1], $user, $db);
            return;
        }

        // ---- 添加备注 ----
        if (preg_match('#^/api/admin/reports/(\d+)/remark$#', $uri, $m) && $method === 'POST') {
            $this->handleReportRemark((int)$m[1], $user, $db);
            return;
        }

        // ---- 上传处理附件 ----
        if (preg_match('#^/api/admin/reports/(\d+)/attach$#', $uri, $m) && $method === 'POST') {
            $this->handleReportAttach((int)$m[1], $user, $db);
            return;
        }

        // ---- 批量导出 (POST body 指定 IDs) ----
        if ($uri === '/api/admin/reports/batch-export' && $method === 'POST') {
            $this->handleAdminBatchExport($user, $db);
            return;
        }

        // ---- 列表 & 普通导出 (GET) ----
        $buildReportQuery = function (?int $regionId, ?int $categoryId, ?string $status, ?string $startDate, ?string $endDate, ?int $handlerId, bool $forExport = false) use ($user, $db): array {
            $selectFields = $forExport
                ? "r.id, r.query_code, r.category_id, c.name as category_name, r.region_id, reg.name as region_name, r.form_data, r.status, r.submitted_at, r.accepted_at, r.handled_at, r.handler_id, a.real_name as handler_name, r.handle_opinion, r.handle_result"
                : "r.*, c.name as category_name, reg.name as region_name, a.real_name as handler_name";

            $sql = "SELECT {$selectFields} FROM reports r
                   LEFT JOIN categories c ON r.category_id = c.id
                   LEFT JOIN regions reg ON r.region_id = reg.id
                   LEFT JOIN admins a ON r.handler_id = a.id
                   WHERE 1=1";

            $params = [];
            $regionIds = $user['region_ids'] ?? [];
            if (!empty($regionIds) && $user['role'] !== 'super_admin') {
                $placeholders = implode(',', array_fill(0, count($regionIds), '?'));
                $sql .= " AND r.region_id IN ($placeholders)";
                $params = array_merge($params, $regionIds);
            }
            if ($regionId) {
                $sql .= " AND r.region_id = ?";
                $params[] = $regionId;
            }
            if ($categoryId) {
                $sql .= " AND r.category_id = ?";
                $params[] = $categoryId;
            }
            if ($status) {
                $sql .= " AND r.status = ?";
                $params[] = $status;
            }
            if ($startDate) {
                $sql .= " AND DATE(r.created_at) >= ?";
                $params[] = $startDate;
            }
            if ($endDate) {
                $sql .= " AND DATE(r.created_at) <= ?";
                $params[] = $endDate;
            }
            if ($handlerId) {
                $sql .= " AND r.handler_id = ?";
                $params[] = $handlerId;
            }

            return [$sql, $params];
        };

        if ($method === 'GET') {
            if (($_GET['action'] ?? '') === 'export') {
                [$sql, $params] = $buildReportQuery(
                    isset($_GET['region_id']) ? (int) $_GET['region_id'] : null,
                    isset($_GET['category_id']) ? (int) $_GET['category_id'] : null,
                    $_GET['status'] ?? null,
                    $_GET['start_date'] ?? null,
                    $_GET['end_date'] ?? null,
                    isset($_GET['handler_id']) ? (int) $_GET['handler_id'] : null,
                    true
                );
                $sql .= " ORDER BY r.id DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $reports = $stmt->fetchAll();

                $statusMap = [
                    'pending' => '待受理',
                    'processing' => '处理中',
                    'supplement' => '待补充',
                    'completed' => '已办结',
                    'rejected' => '已驳回'
                ];

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=reports_' . date('YmdHis') . '.csv');
                echo "\xEF\xBB\xBF";
                echo "ID,查询码,分类,地区,状态,处理人,提交时间,受理时间,办结时间,填报内容,处理意见,办结结果\n";
                foreach ($reports as $r) {
                    $formData = json_decode($r['form_data'] ?? '{}', true);
                    $formDataStr = is_array($formData) ? implode('; ', array_map(fn($k, $v) => "{$k}:{$v}", array_keys($formData), $formData)) : '';
                    $line = [
                        $r['id'],
                        $r['query_code'] ?? '',
                        $r['category_name'] ?? '',
                        $r['region_name'] ?? '',
                        $statusMap[$r['status']] ?? $r['status'],
                        $r['handler_name'] ?? '',
                        $r['submitted_at'] ?? '',
                        $r['accepted_at'] ?? '',
                        $r['handled_at'] ?? '',
                        $formDataStr,
                        $r['handle_opinion'] ?? '',
                        $r['handle_result'] ?? ''
                    ];
                    echo '"' . implode('","', array_map(static fn($v) => str_replace('"', '""', (string) $v), $line)) . '"' . "\n";
                }
                exit;
            }

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? (int) $_GET['page_size'] : 20;
            $regionId = $_GET['region_id'] ?? null;
            $categoryId = $_GET['category_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $handlerId = $_GET['handler_id'] ?? null;

            [$sql, $params] = $buildReportQuery(
                $regionId ? (int) $regionId : null,
                $categoryId ? (int) $categoryId : null,
                $status,
                $startDate,
                $endDate,
                $handlerId ? (int) $handlerId : null
            );

            $countSql = preg_replace('/SELECT r\.\*, c\.name as category_name, reg\.name as region_name, a\.real_name as handler_name FROM/', 'SELECT COUNT(*) as total FROM', $sql, 1);
            if ($countSql === null) {
                $countSql = str_replace('SELECT r.*, c.name as category_name, reg.name as region_name, a.real_name as handler_name FROM', 'SELECT COUNT(*) as total FROM', $sql);
            }
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'] ?? 0;

            $sql .= " ORDER BY r.id DESC LIMIT " . (($page - 1) * $pageSize) . ", $pageSize";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll();

            $statusMap = [
                'pending' => '待受理',
                'processing' => '处理中',
                'supplement' => '待补充',
                'completed' => '已办结',
                'rejected' => '已驳回'
            ];

            foreach ($reports as &$r) {
                if ($r['form_data']) {
                    $r['form_data'] = json_decode($r['form_data'], true);
                }
                if ($r['supplement_data']) {
                    $r['supplement_data'] = json_decode($r['supplement_data'], true);
                }
                if ($r['handle_attachments']) {
                    $r['handle_attachments'] = json_decode($r['handle_attachments'], true);
                }
                $r['status_text'] = $statusMap[$r['status']] ?? $r['status'];
            }

            $this->jsonResponse([
                'success' => true,
                'data' => $reports,
                'pagination' => [
                    'page' => $page,
                    'page_size' => $pageSize,
                    'total' => (int) $total
                ]
            ]);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }

    // ============================================================
    // 统计看板
    // ============================================================
    private function handleAdminReportStats(array $user, PDO $db): void {
        $regionFilter = '';
        $params = [];
        $regionIds = $user['region_ids'] ?? [];
        if (!empty($regionIds) && $user['role'] !== 'super_admin') {
            $placeholders = implode(',', array_fill(0, count($regionIds), '?'));
            $regionFilter = " WHERE region_id IN ($placeholders)";
            $params = $regionIds;
        }

        $result = [
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'supplement' => 0,
            'completed' => 0,
            'rejected' => 0,
            'today' => 0,
            'this_month' => 0,
            'by_category' => [],
            'by_region' => [],
            'trend' => []
        ];

        // 状态统计
        $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM reports {$regionFilter} GROUP BY status" . (!empty($params) ? str_repeat('?', 0) : ''));
        if (!empty($params)) {
            $stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM reports {$regionFilter} GROUP BY status");
            $stmt->execute($params);
        }
        while ($row = $stmt->fetch()) {
            $result[$row['status']] = (int)$row['cnt'];
            $result['total'] += (int)$row['cnt'];
        }

        // 今日
        $todaySql = "SELECT COUNT(*) as cnt FROM reports {$regionFilter} AND DATE(created_at) = CURDATE()";
        $todaySql = str_replace('WHERE AND', 'WHERE', $todaySql);
        if ($regionFilter) {
            $todayStmt = $db->prepare($todaySql);
            $todayStmt->execute($params);
        } else {
            $todayStmt = $db->query($todaySql);
        }
        $result['today'] = (int)$todayStmt->fetchColumn();

        // 本月
        $monthSql = "SELECT COUNT(*) as cnt FROM reports {$regionFilter} AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
        $monthSql = str_replace('WHERE AND', 'WHERE', $monthSql);
        if ($regionFilter) {
            $monthStmt = $db->prepare($monthSql);
            $monthStmt->execute($params);
        } else {
            $monthStmt = $db->query($monthSql);
        }
        $result['this_month'] = (int)$monthStmt->fetchColumn();

        // 按分类
        $catSql = "SELECT c.name, COUNT(r.id) as cnt FROM categories c LEFT JOIN reports r ON c.id = r.category_id";
        if ($regionFilter) {
            $catSql .= " AND r.region_id IN ($placeholders)";
        }
        $catSql .= " GROUP BY c.id ORDER BY cnt DESC";
        if ($regionFilter) {
            $placeholders = implode(',', array_fill(0, count($regionIds), '?'));
            $catSql = "SELECT c.name, COUNT(r.id) as cnt FROM categories c LEFT JOIN reports r ON c.id = r.category_id AND r.region_id IN ($placeholders) GROUP BY c.id ORDER BY cnt DESC";
            $catStmt = $db->prepare($catSql);
            $catStmt->execute($params);
        } else {
            $catStmt = $db->query($catSql);
        }
        while ($row = $catStmt->fetch()) {
            $result['by_category'][] = ['name' => $row['name'], 'count' => (int)$row['cnt']];
        }

        // 按地区
        $regSql = "SELECT reg.name, COUNT(r.id) as cnt FROM regions reg LEFT JOIN reports r ON reg.id = r.region_id WHERE reg.level = 2";
        if ($regionFilter) {
            $regPlaceholders = implode(',', array_fill(0, count($regionIds), '?'));
            $regSql = "SELECT reg.name, COUNT(r.id) as cnt FROM regions reg LEFT JOIN reports r ON reg.id = r.region_id WHERE reg.level = 2 AND reg.id IN ($regPlaceholders) GROUP BY reg.id ORDER BY cnt DESC";
            $regStmt = $db->prepare($regSql);
            $regStmt->execute($params);
        } else {
            $regSql .= " GROUP BY reg.id ORDER BY cnt DESC";
            $regStmt = $db->query($regSql);
        }
        while ($row = $regStmt->fetch()) {
            $result['by_region'][] = ['name' => $row['name'], 'count' => (int)$row['cnt']];
        }

        // 近7天趋势
        $trendParams = $params;
        $trendRegionFilter = $regionFilter ? str_replace('WHERE', 'AND', $regionFilter) : '';
        $trendSql = "SELECT DATE(created_at) as date, COUNT(*) as cnt FROM reports WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) {$trendRegionFilter} GROUP BY DATE(created_at) ORDER BY date ASC";
        if ($regionFilter) {
            $trendPlaceholders = implode(',', array_fill(0, count($regionIds), '?'));
            $trendSql = "SELECT DATE(created_at) as date, COUNT(*) as cnt FROM reports WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND region_id IN ($trendPlaceholders) GROUP BY DATE(created_at) ORDER BY date ASC";
            $trendStmt = $db->prepare($trendSql);
            $trendStmt->execute($params);
        } else {
            $trendStmt = $db->query($trendSql);
        }
        $trendData = [];
        while ($row = $trendStmt->fetch()) {
            $trendData[$row['date']] = (int)$row['cnt'];
        }
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $result['trend'][] = ['date' => $date, 'count' => $trendData[$date] ?? 0];
        }

        $this->jsonResponse(['success' => true, 'data' => $result]);
    }

    // ============================================================
    // 工单详情
    // ============================================================
    private function handleAdminReportDetail(int $reportId, array $user, PDO $db): void {
        $regionIds = $user['region_ids'] ?? [];

        $sql = "SELECT r.*, c.name as category_name, reg.name as region_name, a.real_name as handler_name
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN regions reg ON r.region_id = reg.id
            LEFT JOIN admins a ON r.handler_id = a.id
            WHERE r.id = ?";
        $params = [$reportId];

        if (!empty($regionIds) && $user['role'] !== 'super_admin') {
            $placeholders = implode(',', array_fill(0, count($regionIds), '?'));
            $sql .= " AND r.region_id IN ($placeholders)";
            $params = array_merge($params, $regionIds);
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $report = $stmt->fetch();

        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }

        foreach (['form_data', 'supplement_data', 'handle_attachments'] as $field) {
            if ($report[$field]) {
                $report[$field] = json_decode($report[$field], true);
            }
        }

        $statusMap = [
            'pending' => '待受理',
            'processing' => '处理中',
            'supplement' => '待补充',
            'completed' => '已办结',
            'rejected' => '已驳回'
        ];
        $report['status_text'] = $statusMap[$report['status']] ?? $report['status'];

        $stmtLogs = $db->prepare("SELECT l.*, a.real_name as admin_real_name FROM report_logs l LEFT JOIN admins a ON l.operator_id = a.id WHERE l.report_id = ? ORDER BY l.created_at ASC, l.id ASC");
        $stmtLogs->execute([$reportId]);
        $report['logs'] = $stmtLogs->fetchAll();

        $this->jsonResponse(['success' => true, 'data' => $report]);
    }

    // ============================================================
    // 流转操作公共方法
    // ============================================================
    private function getReportForAction(int $reportId, array $user, PDO $db): ?array {
        $regionIds = $user['region_ids'] ?? [];
        $sql = "SELECT * FROM reports WHERE id = ?";
        $params = [$reportId];
        if (!empty($regionIds) && $user['role'] !== 'super_admin') {
            $placeholders = implode(',', array_fill(0, count($regionIds), '?'));
            $sql .= " AND region_id IN ($placeholders)";
            $params = array_merge($params, $regionIds);
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $report = $stmt->fetch();
        return $report ?: null;
    }

    private function addReportLog(PDO $db, int $reportId, string $action, array $user, string $remark, ?string $fromStatus, ?string $toStatus, ?array $extra = null): void {
        $stmt = $db->prepare("INSERT INTO report_logs (report_id, action, operator_id, operator_type, operator_name, remark, from_status, to_status, extra_data) VALUES (?, ?, ?, 'admin', ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reportId,
            $action,
            $user['id'] ?? null,
            $user['real_name'] ?? $user['username'] ?? '管理员',
            $remark,
            $fromStatus,
            $toStatus,
            $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null
        ]);
    }

    // ============================================================
    // 受理
    // ============================================================
    private function handleReportAccept(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }
        if ($report['status'] !== 'pending') {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '仅待受理工单可执行受理操作']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $remark = $input['remark'] ?? '';

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE reports SET status = 'processing', handler_id = ?, accepted_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id'], $reportId]);
            $this->addReportLog($db, $reportId, 'accept', $user, $remark, 'pending', 'processing');
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '受理成功']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 转派
    // ============================================================
    private function handleReportAssign(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }
        if (!in_array($report['status'], ['pending', 'processing'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '仅待受理或处理中工单可转派']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $targetHandlerId = (int)($input['handler_id'] ?? 0);
        $remark = $input['remark'] ?? '';

        if (!$targetHandlerId) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请选择处理人']);
            return;
        }

        $stmtHandler = $db->prepare("SELECT real_name FROM admins WHERE id = ? LIMIT 1");
        $stmtHandler->execute([$targetHandlerId]);
        $handler = $stmtHandler->fetch();
        if (!$handler) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '处理人不存在']);
            return;
        }

        try {
            $db->beginTransaction();
            $newStatus = $report['status'] === 'pending' ? 'processing' : $report['status'];
            $extra = ['to_handler_id' => $targetHandlerId, 'to_handler_name' => $handler['real_name']];
            $stmt = $db->prepare("UPDATE reports SET handler_id = ?, status = ?, accepted_at = IFNULL(accepted_at, NOW()), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$targetHandlerId, $newStatus, $reportId]);
            $this->addReportLog($db, $reportId, 'assign', $user, $remark, $report['status'], $newStatus, $extra);
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '转派成功']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 要求补充材料
    // ============================================================
    private function handleReportSupplementRequest(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }
        if (!in_array($report['status'], ['processing'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '仅处理中工单可要求补充材料']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $supplementRequest = trim($input['supplement_request'] ?? '');
        if (!$supplementRequest) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请填写补充说明']);
            return;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE reports SET status = 'supplement', supplement_request = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$supplementRequest, $reportId]);
            $this->addReportLog($db, $reportId, 'supplement_request', $user, $supplementRequest, 'processing', 'supplement');
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '已通知用户补充材料']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 办结
    // ============================================================
    private function handleReportComplete(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }
        if (!in_array($report['status'], ['processing', 'supplement'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '仅处理中或待补充工单可办结']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $handleOpinion = trim($input['handle_opinion'] ?? '');
        $handleResult = trim($input['handle_result'] ?? '');
        $handleAttachments = $input['handle_attachments'] ?? null;
        if (!$handleResult) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请填写办结结果']);
            return;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE reports SET status = 'completed', handle_opinion = ?, handle_result = ?, handle_attachments = ?, handled_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([
                $handleOpinion,
                $handleResult,
                $handleAttachments ? json_encode($handleAttachments, JSON_UNESCAPED_UNICODE) : null,
                $reportId
            ]);
            $this->addReportLog($db, $reportId, 'complete', $user, $handleOpinion . ' | ' . $handleResult, $report['status'], 'completed');
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '办结成功']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 驳回
    // ============================================================
    private function handleReportReject(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }
        if (!in_array($report['status'], ['pending', 'processing', 'supplement'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '当前状态不可驳回']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $handleOpinion = trim($input['handle_opinion'] ?? '');
        if (!$handleOpinion) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请填写驳回理由']);
            return;
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE reports SET status = 'rejected', handle_opinion = ?, handled_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$handleOpinion, $reportId]);
            $this->addReportLog($db, $reportId, 'reject', $user, $handleOpinion, $report['status'], 'rejected');
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '已驳回']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 备注
    // ============================================================
    private function handleReportRemark(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $remark = trim($input['remark'] ?? '');
        if (!$remark) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请填写备注内容']);
            return;
        }

        try {
            $db->beginTransaction();
            $newRemark = $report['admin_remark'] ? $report['admin_remark'] . "\n---\n" . $remark : $remark;
            $stmt = $db->prepare("UPDATE reports SET admin_remark = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newRemark, $reportId]);
            $this->addReportLog($db, $reportId, 'remark', $user, $remark, null, null);
            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '备注已添加']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 上传处理附件
    // ============================================================
    private function handleReportAttach(int $reportId, array $user, PDO $db): void {
        $report = $this->getReportForAction($reportId, $user, $db);
        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该工单或无权限访问']);
            return;
        }

        if (empty($_FILES['file'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '未上传文件']);
            return;
        }

        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $stored = uniqid('report_attach_') . '.' . $ext;
        $dir = '/var/www/html/uploads/' . date('Ymd');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $dest = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '文件保存失败']);
            return;
        }
        $relativePath = '/uploads/' . date('Ymd') . '/' . $stored;

        $attachments = $report['handle_attachments'] ? json_decode($report['handle_attachments'], true) : [];
        $attachments[] = ['url' => $relativePath, 'name' => $file['name'], 'uploaded_at' => date('Y-m-d H:i:s')];

        try {
            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE reports SET handle_attachments = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([json_encode($attachments, JSON_UNESCAPED_UNICODE), $reportId]);
            $stmtUp = $db->prepare("INSERT INTO uploads (original_name, stored_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?)");
            $stmtUp->execute([$file['name'], $stored, $relativePath, $file['type'], $file['size']]);
            $this->addReportLog($db, $reportId, 'attach', $user, "上传附件：{$file['name']}", null, null);
            $db->commit();
            $this->jsonResponse(['success' => true, 'data' => ['url' => $relativePath, 'name' => $file['name']]]);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    // ============================================================
    // 批量导出
    // ============================================================
    private function handleAdminBatchExport(array $user, PDO $db): void {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        if (empty($ids) || !is_array($ids)) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请选择要导出的工单']);
            return;
        }

        $regionIds = $user['region_ids'] ?? [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT r.id, r.query_code, c.name as category_name, reg.name as region_name, r.form_data, r.status, r.submitted_at, r.accepted_at, r.handled_at, a.real_name as handler_name, r.handle_opinion, r.handle_result
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN regions reg ON r.region_id = reg.id
            LEFT JOIN admins a ON r.handler_id = a.id
            WHERE r.id IN ($placeholders)";
        $params = $ids;

        if (!empty($regionIds) && $user['role'] !== 'super_admin') {
            $regPlaceholders = implode(',', array_fill(0, count($regionIds), '?'));
            $sql .= " AND r.region_id IN ($regPlaceholders)";
            $params = array_merge($params, $regionIds);
        }
        $sql .= " ORDER BY r.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();

        $statusMap = [
            'pending' => '待受理',
            'processing' => '处理中',
            'supplement' => '待补充',
            'completed' => '已办结',
            'rejected' => '已驳回'
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=reports_batch_' . date('YmdHis') . '.csv');
        echo "\xEF\xBB\xBF";
        echo "ID,查询码,分类,地区,状态,处理人,提交时间,受理时间,办结时间,填报内容,处理意见,办结结果\n";
        foreach ($reports as $r) {
            $formData = json_decode($r['form_data'] ?? '{}', true);
            $formDataStr = is_array($formData) ? implode('; ', array_map(fn($k, $v) => "{$k}:{$v}", array_keys($formData), $formData)) : '';
            $line = [
                $r['id'],
                $r['query_code'] ?? '',
                $r['category_name'] ?? '',
                $r['region_name'] ?? '',
                $statusMap[$r['status']] ?? $r['status'],
                $r['handler_name'] ?? '',
                $r['submitted_at'] ?? '',
                $r['accepted_at'] ?? '',
                $r['handled_at'] ?? '',
                $formDataStr,
                $r['handle_opinion'] ?? '',
                $r['handle_result'] ?? ''
            ];
            echo '"' . implode('","', array_map(static fn($v) => str_replace('"', '""', (string) $v), $line)) . '"' . "\n";
        }
        exit;
    }
}
