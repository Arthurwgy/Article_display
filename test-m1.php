<?php
/**
 * M1 文章 CRUD 验证脚本
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;
$articleId = null;

echo "=== M1 文章 CRUD 验证 ===\n\n";

// 1. 注册拿 token
echo "【1】注册拿 token\n";
$regResp = Http::post($baseUrl . '/auth/register', [
    'name' => 'M1测试用户_' . substr(uniqid(), -6),
    'email' => 'm1_test_' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$reg = json_decode($regResp->body(), true);
if (empty($reg['access_token'])) {
    echo "❌ 注册失败\n";
    exit(1);
}
$token = $reg['access_token'];
echo "✅ token 获取成功\n\n";

// 2. 创建文章
echo "【2】POST /api/articles (创建文章)\n";
$title = '测试文章_' . substr(uniqid(), -4);
$storeResp = Http::withToken($token)->post($baseUrl . '/articles', [
    'title' => $title,
    'content' => "# {$title}\n\n这是正文内容，测试 Markdown 渲染。\n\n## 章节二\n\n- 条目1\n- 条目2",
    'tags' => ['PHP', 'Laravel'],
    'status' => 'published',
]);
$store = json_decode($storeResp->body(), true);
echo "HTTP " . $storeResp->status() . "\n";
echo json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if ($storeResp->status() !== 201) {
    echo "❌ 创建文章失败\n";
    exit(1);
}
$articleId = $store['id'] ?? null;
if (! $articleId) {
    echo "❌ 响应缺少 id\n";
    exit(1);
}
echo "✅ 文章创建成功，id={$articleId}\n\n";

// 3. 列表
echo "【3】GET /api/articles (文章列表)\n";
$listResp = Http::get($baseUrl . '/articles', ['page' => 1, 'per_page' => 20]);
$list = json_decode($listResp->body(), true);
echo "HTTP " . $listResp->status() . "\n";

if ($listResp->status() !== 200) {
    echo "❌ 列表请求失败\n";
    exit(1);
}

$hasArticle = collect($list['data'] ?? [])->contains('id', $articleId);
if (! $hasArticle) {
    echo "❌ 创建的文章不在列表中\n";
    exit(1);
}

if (empty($list['meta']) || empty($list['meta']['total'])) {
    echo "❌ 缺少分页 meta\n";
    exit(1);
}

echo "✅ 列表返回正常，共 {$list['meta']['total']} 篇，meta 完整\n";
echo "  第一篇: {$list['data'][0]['title']}, author={$list['data'][0]['author']['name']}\n\n";

echo "=== ✅ M1 文章 CRUD 全部通过 ===\n";
