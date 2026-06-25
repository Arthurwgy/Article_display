<?php
/**
 * M0 认证 API 验证脚本
 * 用法: php test-auth.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$baseUrl = 'http://127.0.0.1:8000/api';
$userName = '测试用户_' . substr(uniqid(), -6);

echo "=== M0 认证 API 验证 ===\n\n";

// 1. 注册
echo "【1】POST /api/auth/register\n";
$response = Http::post($baseUrl . '/auth/register', [
    'name' => $userName,
    'email' => 'test_m0_' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);

$body = json_decode($response->body(), true);
echo "HTTP " . $response->status() . "\n";
echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($response->status() !== 201 || empty($body['access_token'])) {
    echo "❌ 注册失败\n";
    exit(1);
}

$token = $body['access_token'];
$userId = $body['user']['id'] ?? null;
echo "✅ 注册成功，token: " . substr($token, 0, 40) . "...\n\n";

// 2. 登录
echo "【2】POST /api/auth/login\n";
$loginResponse = Http::post($baseUrl . '/auth/login', [
    'email' => $body['user']['email'],
    'password' => 'password123',
]);
$loginBody = json_decode($loginResponse->body(), true);
echo "HTTP " . $loginResponse->status() . "\n";
echo json_encode($loginBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($loginResponse->status() !== 200 || empty($loginBody['access_token'])) {
    echo "❌ 登录失败\n";
    exit(1);
}
echo "✅ 登录成功\n\n";

// 3. 当前用户
echo "【3】GET /api/auth/me\n";
$meResponse = Http::withToken($token)->get($baseUrl . '/auth/me');
$meBody = json_decode($meResponse->body(), true);
echo "HTTP " . $meResponse->status() . "\n";
echo json_encode($meBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($meResponse->status() !== 200) {
    echo "❌ 获取用户信息失败\n";
    exit(1);
}

// 验证字段
$user = $meBody['user'] ?? [];
$expectedFields = ['id', 'name', 'email', 'role', 'avatar', 'bio', 'coin_balance', 'gold_balance', 'created_at'];
$missing = array_diff($expectedFields, array_keys($user));
if ($missing) {
    echo "❌ 缺少字段: " . implode(', ', $missing) . "\n";
    exit(1);
}

// 验证 coin/gold
if ($user['coin_balance'] !== 0 || $user['gold_balance'] != 0) {
    echo "❌ coin/gold 初始值错误\n";
    exit(1);
}

// 验证 ULID
if (! preg_match('/^[0-9A-Za-z]{26}$/', $user['id'])) {
    echo "❌ ID 不是 ULID: " . $user['id'] . "\n";
    exit(1);
}

echo "✅ 所有字段正确 (id=ULID={$user['id']}, coin_balance={$user['coin_balance']}, gold_balance={$user['gold_balance']})\n\n";
echo "=== ✅ M0 认证全部通过 ===\n";
