<?php
/**
 * 管理后台 - 报表管理路由 trait
 */
trait AdminReportRoutes
{
    private function handleAdminReports(string $method, string $uri, array $user): void
    {
        $db = Database::getConnection();
        $buildReportQuery = function (?int $regionId, ?int $categoryId, bool $forExport = false) use ($user): array {
            $sql = $forExport
                ? "SELECT r.id, r.category_id, c.name as category_name, r.region_id, reg.name as region_name, r.form_data, r.status, r.created_at
                   FROM reports r
                   LEFT JOIN categories c ON r.category_id = c.id
                   LEFT JOIN regions reg ON r.region_id = reg.id
                   WHERE 1=1"
                : "SELECT r.*, c.name as category_name, reg.name as region_name FROM reports r
                   LEFT JOIN categories c ON r.category_id = c.id
                   LEFT JOIN regions reg ON r.region_id = reg.id
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

            return [$sql, $params];
        };

        if ($method === 'GET') {
            if (($_GET['action'] ?? '') === 'export') {
                [$sql, $params] = $buildReportQuery(
                    isset($_GET['region_id']) ? (int) $_GET['region_id'] : null,
                    isset($_GET['category_id']) ? (int) $_GET['category_id'] : null,
                    true
                );
                $sql .= " ORDER BY r.id DESC";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $reports = $stmt->fetchAll();

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=reports_' . date('YmdHis') . '.csv');
                echo "\xEF\xBB\xBF";
                echo "ID,分类,地区,状态,提交时间,填报内容\n";
                foreach ($reports as $r) {
                    $formData = json_decode($r['form_data'] ?? '{}', true);
                    $formDataStr = json_encode($formData, JSON_UNESCAPED_UNICODE);
                    $line = [
                        $r['id'],
                        $r['category_name'] ?? '',
                        $r['region_name'] ?? '',
                        $r['status'] ?? '',
                        $r['created_at'] ?? '',
                        $formDataStr ?: ''
                    ];
                    echo '"' . implode('","', array_map(static fn($v) => str_replace('"', '""', (string) $v), $line)) . '"' . "\n";
                }
                exit;
            }

            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? (int) $_GET['page_size'] : 20;
            $regionId = $_GET['region_id'] ?? null;
            $categoryId = $_GET['category_id'] ?? null;

            [$sql, $params] = $buildReportQuery(
                $regionId ? (int) $regionId : null,
                $categoryId ? (int) $categoryId : null
            );

            $countSql = str_replace('SELECT r.*, c.name as category_name, reg.name as region_name', 'SELECT COUNT(*) as total', $sql);
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'] ?? 0;

            $sql .= " ORDER BY r.id DESC LIMIT " . (($page - 1) * $pageSize) . ", $pageSize";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $reports = $stmt->fetchAll();

            foreach ($reports as &$r) {
                if ($r['form_data']) {
                    $r['form_data'] = json_decode($r['form_data'], true);
                }
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

        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!empty($input['action']) && $input['action'] === 'export') {
                [$sql, $params] = $buildReportQuery(
                    !empty($input['region_id']) ? (int) $input['region_id'] : null,
                    !empty($input['category_id']) ? (int) $input['category_id'] : null,
                    true
                );
                $sql .= " ORDER BY r.id DESC";

                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $reports = $stmt->fetchAll();

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=reports_' . date('YmdHis') . '.csv');
                echo "\xEF\xBB\xBF";
                echo "ID,分类,地区,状态,提交时间\n";

                foreach ($reports as $r) {
                    $formData = json_decode($r['form_data'] ?? '{}', true);
                    $formDataStr = '';
                    foreach ($formData as $k => $v) {
                        $formDataStr .= "$k: $v; ";
                    }
                    echo "{$r['id']},{$r['category_name']},{$r['region_name']},{$r['status']},{$r['created_at']}\n";
                }
                exit;
            }

            $this->jsonResponse(['success' => false, 'message' => '不支持的操作']);
            return;
        }

        $this->jsonResponse(['success' => false, 'message' => '不支持的方法']);
    }
}
