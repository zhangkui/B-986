<?php
/**
 * 路由分发器
 * 公开 API：首页/分类/知识/注意事项/表单定义/提交 - 无需认证
 * 管理 API：/api/admin/* - 需要认证 + 权限
 *
 * 模块化：各路由处理逻辑通过 trait 拆分至 modules/ 目录
 */

// 加载路由模块
require_once __DIR__ . '/modules/PublicRoutes.php';
require_once __DIR__ . '/modules/AdminAuthRoutes.php';
require_once __DIR__ . '/modules/AdminContentRoutes.php';
require_once __DIR__ . '/modules/AdminFormRoutes.php';
require_once __DIR__ . '/modules/AdminManageRoutes.php';
require_once __DIR__ . '/modules/AdminReportRoutes.php';

class Router
{
    use PublicRoutes;
    use AdminAuthRoutes;
    use AdminContentRoutes;
    use AdminFormRoutes;
    use AdminManageRoutes;
    use AdminReportRoutes;

    public function dispatch(string $method, string $uri): void
    {
        // 健康检查
        if ($uri === '/api/health' || $uri === '/health') {
            $this->jsonResponse(['status' => 'ok', 'service' => 'labelease-backend', 'php' => PHP_VERSION]);
            return;
        }

        // ---- 公开 API ----
        // 系统配置
        if ($uri === '/api/config' && $method === 'GET') {
            $this->handleGetConfig();
            return;
        }

        // 分类列表
        if ($uri === '/api/categories' && $method === 'GET') {
            $this->handleGetCategories();
            return;
        }

        // 知识列表
        if (preg_match('#^/api/knowledge(?:/(\d+))?$#', $uri, $m) && $method === 'GET') {
            $this->handleGetKnowledge($m[1] ?? null);
            return;
        }

        // 注意事项
        if (preg_match('#^/api/notices/(\d+)$#', $uri, $m) && $method === 'GET') {
            $this->handleGetNotice((int) $m[1]);
            return;
        }

        // 表单字段定义
        if (preg_match('#^/api/form-fields/(\d+)$#', $uri, $m) && $method === 'GET') {
            $this->handleGetFormFields((int) $m[1]);
            return;
        }

        // 地区列表
        if ($uri === '/api/regions' && $method === 'GET') {
            $this->handleGetRegions();
            return;
        }

        // 提交报告
        if ($uri === '/api/reports' && $method === 'POST') {
            $this->handleSubmitReport();
            return;
        }

        // 文件上传
        if ($uri === '/api/upload' && $method === 'POST') {
            $this->handleUpload();
            return;
        }

        // ---- 管理员登录 ----
        if ($uri === '/api/admin/login' && $method === 'POST') {
            $this->handleAdminLogin();
            return;
        }

        // ---- 需要认证的管理 API ----
        if (str_starts_with($uri, '/api/admin/')) {
            $user = AuthMiddleware::requireAuth();

            // 管理员信息
            if ($uri === '/api/admin/profile' && $method === 'GET') {
                $this->jsonResponse(['success' => true, 'data' => $user]);
                return;
            }

            // 内容管理 (配置/知识/注意事项/广告图)
            if (str_starts_with($uri, '/api/admin/config')) {
                AuthMiddleware::requirePermission('content');
                $this->handleAdminConfig($method, $uri);
                return;
            }
            if (str_starts_with($uri, '/api/admin/knowledge')) {
                AuthMiddleware::requirePermission('content');
                $this->handleAdminKnowledge($method, $uri);
                return;
            }
            if (str_starts_with($uri, '/api/admin/notices')) {
                AuthMiddleware::requirePermission('content');
                $this->handleAdminNotices($method, $uri);
                return;
            }

            // 表单配置
            if (str_starts_with($uri, '/api/admin/form-fields')) {
                AuthMiddleware::requirePermission('form');
                $this->handleAdminFormFields($method, $uri);
                return;
            }

            // 管理员管理
            if (str_starts_with($uri, '/api/admin/admins')) {
                AuthMiddleware::requirePermission('admin');
                $this->handleAdminManagement($method, $uri);
                return;
            }

            // 报表
            if (str_starts_with($uri, '/api/admin/reports')) {
                AuthMiddleware::requirePermission('report');
                $this->handleAdminReports($method, $uri, $user);
                return;
            }

            // 分类管理
            if (str_starts_with($uri, '/api/admin/categories')) {
                AuthMiddleware::requirePermission('content');
                $this->handleAdminCategories($method, $uri);
                return;
            }

            http_response_code(404);
            $this->jsonResponse(['success' => false, 'message' => '管理接口未找到']);
            return;
        }

        // 404
        http_response_code(404);
        $this->jsonResponse(['success' => false, 'message' => '接口不存在']);
    }

    // ========== 工具方法 ==========

    private function jsonResponse(array $data, int $code = 200): void
    {
        if (http_response_code() === 200) {
            http_response_code($code);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
