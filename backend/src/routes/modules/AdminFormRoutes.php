<?php
/**
 * 管理后台 - 表单配置路由 trait
 */
trait AdminFormRoutes
{
    private function handleAdminFormFields(string $method, string $uri): void
    {
        $db = Database::getConnection();

        if ($method === 'GET') {
            $categoryId = $_GET['category_id'] ?? null;

            $sql = "SELECT * FROM form_fields WHERE 1=1";
            $params = [];

            if ($categoryId) {
                $sql .= " AND category_id = ?";
                $params[] = (int) $categoryId;
            }

            $sql .= " ORDER BY sort_order, id";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $fields = $stmt->fetchAll();
            foreach ($fields as &$f) {
                if ($f['field_options']) {
                    $f['field_options'] = json_decode($f['field_options'], true);
                }
            }
            $this->jsonResponse(['success' => true, 'data' => $fields]);
            return;
        }

        if ($method === 'POST' || $method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['field_name']) || empty($input['field_type'])) {
                http_response_code(400);
                $this->jsonResponse(['success' => false, 'message' => '缺少必填字段']);
                return;
            }

            $fieldName = $input['field_name'];
            $fieldType = $input['field_type'];
            $fieldOptions = $input['field_options'] ? json_encode($input['field_options'], JSON_UNESCAPED_UNICODE) : null;
            $isRequired = $input['is_required'] ?? 0;
            $categoryId = $input['category_id'] ?? null;
            $sortOrder = $input['sort_order'] ?? 0;
            $status = $input['status'] ?? 1;

            if (!empty($input['id'])) {
                $stmt = $db->prepare("UPDATE form_fields SET field_name=?, field_type=?, field_options=?, is_required=?, category_id=?, sort_order=?, status=? WHERE id=?");
                $stmt->execute([$fieldName, $fieldType, $fieldOptions, $isRequired, $categoryId, $sortOrder, $status, $input['id']]);
                $this->jsonResponse(['success' => true, 'message' => '更新成功']);
            } else {
                $stmt = $db->prepare("INSERT INTO form_fields (field_name, field_type, field_options, is_required, category_id, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fieldName, $fieldType, $fieldOptions, $isRequired, $categoryId, $sortOrder, $status]);
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

            $stmt = $db->prepare("DELETE FROM form_fields WHERE id = ?");
            $stmt->execute([$input['id']]);
            $this->jsonResponse(['success' => true, 'message' => '删除成功']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }
}
