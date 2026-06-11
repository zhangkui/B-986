<?php
/**
 * 管理员登录处理 trait
 */
trait AdminAuthRoutes
{
    private function handleAdminLogin(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['username']) || empty($input['password'])) {
            http_response_code(400);
            $this->jsonResponse(['success' => false, 'message' => '请输入用户名和密码']);
            return;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? AND status = 1");
        $stmt->execute([$input['username']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($input['password'], $admin['password'])) {
            http_response_code(401);
            $this->jsonResponse(['success' => false, 'message' => '用户名或密码错误']);
            return;
        }

        // 更新最后登录时间
        $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

        $token = AuthMiddleware::generateToken([
            'id' => $admin['id'],
            'username' => $admin['username'],
            'real_name' => $admin['real_name'],
            'role' => $admin['role'],
            'permissions' => json_decode($admin['permissions'] ?: '[]', true),
            'region_ids' => json_decode($admin['region_ids'] ?: '[]', true),
        ]);

        $this->jsonResponse([
            'success' => true,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'admin' => [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'real_name' => $admin['real_name'],
                    'role' => $admin['role'],
                    'permissions' => json_decode($admin['permissions'] ?: '[]', true),
                ]
            ]
        ]);
    }
}
