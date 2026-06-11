<?php
/**
 * 公开 API 路由处理 trait
 * 首页配置/分类/知识/注意事项/表单定义/地区/提交/上传/举报查询
 */
trait PublicRoutes {
    private function handleGetConfig(): void {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT config_key, config_value, config_type, page_level, category_id FROM sys_config ORDER BY sort_order");
        $rows = $stmt->fetchAll();
        $config = [];
        foreach ($rows as $row) {
            $config[$row['config_key']] = $row;
        }
        $this->jsonResponse(['success' => true, 'data' => $config]);
    }

    private function handleGetCategories(): void {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order");
        $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    private function handleGetKnowledge(?string $categoryId): void {
        $db = Database::getConnection();
        $pageLevel = $_GET['page_level'] ?? null;

        $sql = "SELECT * FROM knowledge WHERE status = 1";
        $params = [];

        if ($categoryId) {
            $sql .= " AND category_id = ?";
            $params[] = (int)$categoryId;
        }
        if ($pageLevel) {
            $sql .= " AND page_level = ?";
            $params[] = (int)$pageLevel;
        }
        $sql .= " ORDER BY sort_order, id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    private function handleGetNotice(int $categoryId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM notices WHERE category_id = ? ORDER BY sort_order LIMIT 1");
        $stmt->execute([$categoryId]);
        $notice = $stmt->fetch();
        $this->jsonResponse(['success' => true, 'data' => $notice ?: null]);
    }

    private function handleGetFormFields(int $categoryId): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM form_fields WHERE category_id = ? AND status = 1 ORDER BY sort_order");
        $stmt->execute([$categoryId]);
        $fields = $stmt->fetchAll();
        foreach ($fields as &$f) {
            if ($f['field_options']) {
                $f['field_options'] = json_decode($f['field_options'], true);
            }
        }
        $this->jsonResponse(['success' => true, 'data' => $fields]);
    }

    private function handleGetRegions(): void {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM regions WHERE status = 1 ORDER BY level, sort_order");
        $regions = $stmt->fetchAll();
        // 树形结构
        $tree = [];
        $map = [];
        foreach ($regions as $r) {
            $r['children'] = [];
            $map[$r['id']] = $r;
        }
        foreach ($map as &$item) {
            if ($item['parent_id'] && isset($map[$item['parent_id']])) {
                $map[$item['parent_id']]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        $this->jsonResponse(['success' => true, 'data' => $tree]);
    }

    private function generateQueryCode(PDO $db): string {
        $prefix = 'JB';
        $datePart = date('Ymd');
        $attempts = 0;
        while ($attempts < 10) {
            $randomPart = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $code = $prefix . $datePart . $randomPart;
            $stmt = $db->prepare("SELECT COUNT(*) FROM reports WHERE query_code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetchColumn() == 0) {
                return $code;
            }
            $attempts++;
        }
        return $prefix . $datePart . substr(md5(uniqid('', true)), 0, 4);
    }

    private function handleSubmitReport(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['category_id']) || !isset($input['form_data'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '缺少必填字段']);
            return;
        }

        $db = Database::getConnection();
        $queryCode = $this->generateQueryCode($db);

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("INSERT INTO reports (query_code, category_id, region_id, form_data, status, submitted_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([
                $queryCode,
                (int)$input['category_id'],
                $input['region_id'] ?? null,
                json_encode($input['form_data'], JSON_UNESCAPED_UNICODE)
            ]);
            $reportId = (int)$db->lastInsertId();

            $stmtLog = $db->prepare("INSERT INTO report_logs (report_id, action, operator_id, operator_type, operator_name, from_status, to_status) VALUES (?, 'submit', NULL, 'user', '用户提交', NULL, 'pending')");
            $stmtLog->execute([$reportId]);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => '提交成功',
                'data' => [
                    'id' => $reportId,
                    'query_code' => $queryCode
                ]
            ]);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '提交失败：' . $e->getMessage()]);
        }
    }

    private function handleGetReportDetail(string $queryCode): void {
        if (!$queryCode) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请提供查询码']);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT r.*, c.name as category_name, reg.name as region_name, a.real_name as handler_name
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN regions reg ON r.region_id = reg.id
            LEFT JOIN admins a ON r.handler_id = a.id
            WHERE r.query_code = ? LIMIT 1");
        $stmt->execute([$queryCode]);
        $report = $stmt->fetch();

        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该举报记录']);
            return;
        }

        if ($report['form_data']) {
            $report['form_data'] = json_decode($report['form_data'], true);
        }
        if ($report['supplement_data']) {
            $report['supplement_data'] = json_decode($report['supplement_data'], true);
        }
        if ($report['handle_attachments']) {
            $report['handle_attachments'] = json_decode($report['handle_attachments'], true);
        }

        $statusMap = [
            'pending' => '待受理',
            'processing' => '处理中',
            'supplement' => '待补充',
            'completed' => '已办结',
            'rejected' => '已驳回'
        ];
        $report['status_text'] = $statusMap[$report['status']] ?? $report['status'];

        $stmtLogs = $db->prepare("SELECT * FROM report_logs WHERE report_id = ? ORDER BY created_at ASC, id ASC");
        $stmtLogs->execute([$report['id']]);
        $report['logs'] = $stmtLogs->fetchAll();

        $this->jsonResponse(['success' => true, 'data' => $report]);
    }

    private function handleBatchGetReports(): void {
        $codes = $_GET['codes'] ?? '';
        if (!$codes) {
            $this->jsonResponse(['success' => true, 'data' => []]);
            return;
        }

        $codeList = array_filter(array_map('trim', explode(',', $codes)));
        if (empty($codeList)) {
            $this->jsonResponse(['success' => true, 'data' => []]);
            return;
        }

        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($codeList), '?'));
        $stmt = $db->prepare("SELECT r.id, r.query_code, r.category_id, r.region_id, r.status, r.submitted_at, r.accepted_at, r.handled_at,
            c.name as category_name, reg.name as region_name
            FROM reports r
            LEFT JOIN categories c ON r.category_id = c.id
            LEFT JOIN regions reg ON r.region_id = reg.id
            WHERE r.query_code IN ($placeholders)
            ORDER BY r.submitted_at DESC");
        $stmt->execute($codeList);

        $reports = $stmt->fetchAll();
        $statusMap = [
            'pending' => '待受理',
            'processing' => '处理中',
            'supplement' => '待补充',
            'completed' => '已办结',
            'rejected' => '已驳回'
        ];
        foreach ($reports as &$r) {
            $r['status_text'] = $statusMap[$r['status']] ?? $r['status'];
        }

        $this->jsonResponse(['success' => true, 'data' => $reports]);
    }

    private function handleSupplementReport(string $queryCode): void {
        if (!$queryCode) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请提供查询码']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['supplement_data'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '缺少补充材料数据']);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, status FROM reports WHERE query_code = ? LIMIT 1");
        $stmt->execute([$queryCode]);
        $report = $stmt->fetch();

        if (!$report) {
            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '未找到该举报记录']);
            return;
        }

        if ($report['status'] !== 'supplement') {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '当前状态无需补充材料']);
            return;
        }

        try {
            $db->beginTransaction();

            $stmtUpdate = $db->prepare("UPDATE reports SET supplement_data = ?, status = 'processing', updated_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([
                json_encode($input['supplement_data'], JSON_UNESCAPED_UNICODE),
                $report['id']
            ]);

            $remark = $input['remark'] ?? '';
            $stmtLog = $db->prepare("INSERT INTO report_logs (report_id, action, operator_id, operator_type, operator_name, remark, from_status, to_status) VALUES (?, 'supplement_submit', NULL, 'user', '用户提交补充', ?, 'supplement', 'processing')");
            $stmtLog->execute([$report['id'], $remark]);

            $db->commit();
            $this->jsonResponse(['success' => true, 'message' => '补充材料提交成功']);
        } catch (Throwable $e) {
            $db->rollBack();
            http_response_code(500);
            $this->jsonResponse(['success' => false, 'message' => '提交失败：' . $e->getMessage()]);
        }
    }

    private function handleUpload(): void {
        if (empty($_FILES['file'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '未上传文件']);
            return;
        }

        $file = $_FILES['file'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $stored = uniqid('upload_') . '.' . $ext;
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

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO uploads (original_name, stored_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$file['name'], $stored, $relativePath, $file['type'], $file['size']]);

        $this->jsonResponse(['success' => true, 'data' => ['url' => $relativePath, 'id' => $db->lastInsertId()]]);
    }
}
