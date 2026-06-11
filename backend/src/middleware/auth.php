<?php
/**
 * 认证中间件骨架
 * - JWT Token 验证
 * - RBAC 权限检查
 * - 地区权限检查
 */

class AuthMiddleware {
    private const SECRET_KEY = 'labelease-986-jwt-secret-key';
    private const TOKEN_EXPIRE = 86400; // 24h

    /**
     * 生成 JWT Token
     */
    public static function generateToken(array $payload): string {
        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload['iat'] = time();
        $payload['exp'] = time() + self::TOKEN_EXPIRE;
        $payloadEncoded = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $signature = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payloadEncoded}", self::SECRET_KEY, true));
        return "{$header}.{$payloadEncoded}.{$signature}";
    }

    /**
     * 验证并解析 Token
     */
    public static function verifyToken(): ?array {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', "{$header}.{$payload}", self::SECRET_KEY, true));

        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (!$data || ($data['exp'] ?? 0) < time()) {
            return null;
        }

        return $data;
    }

    /**
     * 要求认证 - 未登录返回 401
     */
    public static function requireAuth(): array {
        $user = self::verifyToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => '未登录或登录已过期'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $user;
    }

    /**
     * 检查功能权限
     */
    public static function requirePermission(string $permission): array {
        $user = self::requireAuth();
        if ($user['role'] === 'super_admin') {
            return $user;
        }
        $permissions = $user['permissions'] ?? [];
        if (!in_array($permission, $permissions)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => '无权限访问'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        return $user;
    }

    /**
     * 检查地区权限
     */
    public static function checkRegionAccess(array $user, int $regionId): bool {
        if ($user['role'] === 'super_admin') {
            return true;
        }
        $regionIds = $user['region_ids'] ?? [];
        return in_array($regionId, $regionIds);
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
