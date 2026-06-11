<?php
/**
 * 管理后台 - 内容管理路由 trait
 * 系统配置/知识管理/注意事项/分类管理
 */
trait AdminContentRoutes
{
    private function handleAdminConfig(string $method, string $uri): void
    {
        $db = Database::getConnection();

        if ($method === 'GET') {
            $stmt = $db->query("SELECT * FROM sys_config ORDER BY page_level, sort_order, id");
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            return;
        }

        if ($method === 'POST' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['config_key'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少配置键']);
                return;
            }

            $configKey = $input['config_key'];
            $configValue = $input['config_value'] ?? '';
            $configType = $input['config_type'] ?? 'text';
            $pageLevel = $input['page_level'] ?? 0;
            $categoryId = $input['category_id'] ?? null;

            $stmt = $db->prepare("SELECT id FROM sys_config WHERE config_key = ?");
            $stmt->execute([$configKey]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $db->prepare("UPDATE sys_config SET config_value = ?, config_type = ?, page_level = ?, category_id = ? WHERE config_key = ?");
                $stmt->execute([$configValue, $configType, $pageLevel, $categoryId, $configKey]);
            } else {
                $stmt = $db->prepare("INSERT INTO sys_config (config_key, config_value, config_type, page_level, category_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$configKey, $configValue, $configType, $pageLevel, $categoryId]);
            }

            $this->jsonResponse(['success' => true, 'message' => '保存成功']);
            return;
        }

        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少ID']);
                return;
            }

            $stmt = $db->prepare("DELETE FROM sys_config WHERE id = ?");
            $stmt->execute([$input['id']]);
            $this->jsonResponse(['success' => true, 'message' => '删除成功']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }

    private function handleAdminKnowledge(string $method, string $uri): void
    {
        $db = Database::getConnection();

        if ($method === 'GET') {
            $categoryId = $_GET['category_id'] ?? null;
            $pageLevel = $_GET['page_level'] ?? null;

            $sql = "SELECT * FROM knowledge WHERE 1=1";
            $params = [];

            if ($categoryId) {
                $sql .= " AND category_id = ?";
                $params[] = (int) $categoryId;
            }
            if ($pageLevel) {
                $sql .= " AND page_level = ?";
                $params[] = (int) $pageLevel;
            }

            $sql .= " ORDER BY sort_order, id DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            return;
        }

        if ($method === 'POST' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['title'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少标题']);
                return;
            }

            $title = $input['title'];
            $summary = $input['summary'] ?? '';
            $content = $input['content'] ?? '';
            $categoryId = $input['category_id'] ?? null;
            $pageLevel = $input['page_level'] ?? 1;
            $sortOrder = $input['sort_order'] ?? 0;
            $status = $input['status'] ?? 1;

            if (!empty($input['id'])) {
                $stmt = $db->prepare("UPDATE knowledge SET title=?, summary=?, content=?, category_id=?, page_level=?, sort_order=?, status=? WHERE id=?");
                $stmt->execute([$title, $summary, $content, $categoryId, $pageLevel, $sortOrder, $status, $input['id']]);
                $this->jsonResponse(['success' => true, 'message' => '更新成功']);
            } else {
                $stmt = $db->prepare("INSERT INTO knowledge (title, summary, content, category_id, page_level, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $summary, $content, $categoryId, $pageLevel, $sortOrder, $status]);
                $this->jsonResponse(['success' => true, 'message' => '添加成功', 'data' => ['id' => $db->lastInsertId()]]);
            }
            return;
        }

        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少ID']);
                return;
            }

            $stmt = $db->prepare("DELETE FROM knowledge WHERE id = ?");
            $stmt->execute([$input['id']]);
            $this->jsonResponse(['success' => true, 'message' => '删除成功']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }

    private function handleAdminNotices(string $method, string $uri): void
    {
        $db = Database::getConnection();

        if ($method === 'GET') {
            $categoryId = $_GET['category_id'] ?? null;

            $sql = "SELECT * FROM notices WHERE 1=1";
            $params = [];

            if ($categoryId) {
                $sql .= " AND category_id = ?";
                $params[] = (int) $categoryId;
            }

            $sql .= " ORDER BY sort_order, id DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            return;
        }

        if ($method === 'POST' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['title']) || empty($input['content'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少必填字段']);
                return;
            }

            $title = $input['title'];
            $content = $input['content'];
            $categoryId = $input['category_id'] ?? null;
            $sortOrder = $input['sort_order'] ?? 0;

            if (!empty($input['id'])) {
                $stmt = $db->prepare("UPDATE notices SET title=?, content=?, category_id=?, sort_order=? WHERE id=?");
                $stmt->execute([$title, $content, $categoryId, $sortOrder, $input['id']]);
                $this->jsonResponse(['success' => true, 'message' => '更新成功']);
            } else {
                $stmt = $db->prepare("INSERT INTO notices (title, content, category_id, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $categoryId, $sortOrder]);
                $this->jsonResponse(['success' => true, 'message' => '添加成功', 'data' => ['id' => $db->lastInsertId()]]);
            }
            return;
        }

        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少ID']);
                return;
            }

            $stmt = $db->prepare("DELETE FROM notices WHERE id = ?");
            $stmt->execute([$input['id']]);
            $this->jsonResponse(['success' => true, 'message' => '删除成功']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }

    private function handleAdminCategories(string $method, string $uri): void
    {
        $db = Database::getConnection();

        if ($method === 'GET') {
            $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order, id");
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
            return;
        }

        if ($method === 'POST' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['name'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少分类名称']);
                return;
            }

            $name = $input['name'];
            $sortOrder = $input['sort_order'] ?? 0;
            $status = $input['status'] ?? 1;

            if (!empty($input['id'])) {
                $stmt = $db->prepare("UPDATE categories SET name=?, sort_order=?, status=? WHERE id=?");
                $stmt->execute([$name, $sortOrder, $status, $input['id']]);
                $this->jsonResponse(['success' => true, 'message' => '更新成功']);
            } else {
                $stmt = $db->prepare("INSERT INTO categories (name, sort_order, status) VALUES (?, ?, ?)");
                $stmt->execute([$name, $sortOrder, $status]);
                $this->jsonResponse(['success' => true, 'message' => '添加成功', 'data' => ['id' => $db->lastInsertId()]]);
            }
            return;
        }

        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少ID']);
                return;
            }

            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$input['id']]);
            $this->jsonResponse(['success' => true, 'message' => '删除成功']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }
}
