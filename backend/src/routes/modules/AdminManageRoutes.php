<?php
/**
 * 管理后台 - 管理员管理路由 trait
 */
trait AdminManageRoutes
{
    private function handleAdminManagement(string $method, string $uri): void
    {
        $db = Database::getConnection();

        if ($method === 'GET') {
            $stmt = $db->query("SELECT id, username, real_name, role, permissions, region_ids, status, last_login FROM admins ORDER BY id");
            $admins = $stmt->fetchAll();
            foreach ($admins as &$a) {
                $a['permissions'] = json_decode($a['permissions'] ?: '[]', true);
                $a['region_ids'] = json_decode($a['region_ids'] ?: '[]', true);
            }
            $this->jsonResponse(['success' => true, 'data' => $admins]);
            return;
        }

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['username']) || empty($input['password'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少必填字段']);
                return;
            }

            $username = $input['username'];
            $password = password_hash($input['password'], PASSWORD_BCRYPT);
            $realName = $input['real_name'] ?? '';
            $role = $input['role'] ?? 'admin';
            $permissions = json_encode($input['permissions'] ?? [], JSON_UNESCAPED_UNICODE);
            $regionIds = json_encode($input['region_ids'] ?? [], JSON_UNESCAPED_UNICODE);
            $status = $input['status'] ?? 1;

            $stmt = $db->prepare("INSERT INTO admins (username, password, real_name, role, permissions, region_ids, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $password, $realName, $role, $permissions, $regionIds, $status]);
            $this->jsonResponse(['success' => true, 'message' => '添加成功', 'data' => ['id' => $db->lastInsertId()]]);
            return;
        }

        if ($method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['id'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少ID']);
                return;
            }

            $id = $input['id'];
            $updates = [];
            $params = [];

            if (isset($input['real_name'])) {
                $updates[] = "real_name = ?";
                $params[] = $input['real_name'];
            }
            if (isset($input['password']) && $input['password']) {
                $updates[] = "password = ?";
                $params[] = password_hash($input['password'], PASSWORD_BCRYPT);
            }
            if (isset($input['role'])) {
                $updates[] = "role = ?";
                $params[] = $input['role'];
            }
            if (isset($input['permissions'])) {
                $updates[] = "permissions = ?";
                $params[] = json_encode($input['permissions'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($input['region_ids'])) {
                $updates[] = "region_ids = ?";
                $params[] = json_encode($input['region_ids'], JSON_UNESCAPED_UNICODE);
            }
            if (isset($input['status'])) {
                $updates[] = "status = ?";
                $params[] = $input['status'];
            }

            if (empty($updates)) {
                $this->jsonResponse(['success' => false, 'message' => '没有要更新的字段']);
                return;
            }

            $params[] = $id;
            $sql = "UPDATE admins SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $this->jsonResponse(['success' => true, 'message' => '更新成功']);
            return;
        }

        if ($method === 'DELETE') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (empty($input['id'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少ID']);
                return;
            }

            $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$input['id']]);
            $this->jsonResponse(['success' => true, 'message' => '删除成功']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }
}
