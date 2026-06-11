<?php
/**
 * 药品医疗器械化妆品填报平台 - API 入口
 * PHP 8.3 | REST API
 */

declare(strict_types=1);

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// UTF-8
header('Content-Type: application/json; charset=utf-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 加载配置和基础组件
require_once __DIR__ . '/src/config/database.php';
require_once __DIR__ . '/src/middleware/auth.php';
require_once __DIR__ . '/src/routes/router.php';

// 获取请求信息
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

// 路由分发
try {
    $router = new Router();
    $router->dispatch($method, $uri);
} catch (Throwable $e) {
    error_log("Unhandled error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误',
        'error' => (getenv('APP_DEBUG') === 'true') ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
