<?php
/**
 * 公开 API 路由处理 trait
 * 首页配置/分类/知识/注意事项/表单定义/地区/提交/上传
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

    private function handleSubmitReport(): void {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['category_id']) || !isset($input['form_data'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '缺少必填字段']);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO reports (category_id, region_id, form_data, status) VALUES (?, ?, ?, 'submitted')");
        $stmt->execute([
            (int)$input['category_id'],
            $input['region_id'] ?? null,
            json_encode($input['form_data'], JSON_UNESCAPED_UNICODE)
        ]);
        $this->jsonResponse(['success' => true, 'message' => '提交成功', 'data' => ['id' => $db->lastInsertId()]]);
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
