<?php
/**
 * M2 审核流程 API 验证脚本
 * 用法: php test-m2.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;
$articleId = null;
$userName = 'M2测试用户_' . substr(uniqid(), -6);
$allPassed = true;

function pass($msg) { echo "✅ {$msg}\n"; }
function fail($msg) { global $allPassed; $allPassed = false; echo "❌ {$msg}\n"; }
function info($msg) { echo "ℹ️  {$msg}\n"; }

echo "=== M2 审核流程 API 验证 ===\n\n";

// ── 1. 注册 + 登录 ──────────────────────────────────────────
echo "【1】注册新用户\n";
$regResp = Http::post($baseUrl . '/auth/register', [
    'name' => $userName,
    'email' => 'm2_test_' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);
$reg = json_decode($regResp->body(), true);
if ($regResp->status() !== 201 || empty($reg['access_token'])) {
    fail("注册失败 HTTP={$regResp->status()}");
    exit(1);
}
$token = $reg['access_token'];
pass("注册成功，token: " . substr($token, 0, 30) . "...");

// ── 2. 创建草稿 ────────────────────────────────────────────
echo "\n【2】创建草稿文章\n";
$title = 'M2测试文章_' . substr(uniqid(), -4);
$storeResp = Http::withToken($token)->post($baseUrl . '/articles', [
    'title' => $title,
    'content' => "# {$title}\n\n这是正文，测试审核流程。\n\n## 第二节\n\n测试内容。",
    'tags' => ['测试'],
    'status' => 'draft',
]);
$store = json_decode($storeResp->body(), true);
if ($storeResp->status() !== 201) {
    fail("创建文章失败 HTTP={$storeResp->status()}, body: " . json_encode($store));
    exit(1);
}
$articleId = $store['id'] ?? null;
if (!$articleId) {
    fail("响应缺少 id");
    exit(1);
}
pass("草稿创建成功 id={$articleId}");

// ── 3. 验证初始状态 ────────────────────────────────────────
echo "\n【3】验证文章初始状态\n";
$detailResp = Http::withToken($token)->get($baseUrl . "/articles/{$articleId}");
$detail = json_decode($detailResp->body(), true);
if ($detail['status'] !== 'draft') {
    fail("初始状态应为 draft，实际: {$detail['status']}");
} else {
    pass("状态正确: draft");
}

// ── 4. 提交审核 ────────────────────────────────────────────
echo "\n【4】POST /api/articles/{id}/submit (提交审核)\n";
$submitResp = Http::withToken($token)->post($baseUrl . "/articles/{$articleId}/submit");
$submitBody = json_decode($submitResp->body(), true);
echo "HTTP {$submitResp->status()}\n";
echo json_encode($submitBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
if ($submitResp->status() !== 200) {
    fail("提交审核失败 HTTP={$submitResp->status()}");
} else {
    $newStatus = $submitBody['status'] ?? ($detail['status'] ?? null);
    // 机审可能 auto_reject 也可能 first_pass，状态不为 draft 即可
    if (!in_array($newStatus, ['pending', 'first_pass', 'published', 'first_reject'])) {
        fail("提交后状态异常: {$newStatus}");
    } else {
        pass("提交审核成功，状态: {$newStatus}");
    }
}

// ── 5. 审核日志 ────────────────────────────────────────────
echo "\n【5】GET /api/articles/{id}/review-logs (审核日志)\n";
$logsResp = Http::withToken($token)->get($baseUrl . "/articles/{$articleId}/review-logs");
$logsBody = json_decode($logsResp->body(), true);
echo "HTTP {$logsResp->status()}\n";
echo json_encode($logsBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
if ($logsResp->status() !== 200) {
    fail("获取审核日志失败 HTTP={$logsResp->status()}");
} elseif (empty($logsBody['data'])) {
    fail("审核日志为空");
} else {
    $log = $logsBody['data'][0];
    $validActions = ['submit', 'auto_reject', 'first_pass', 'first_reject',
                     'modify_required', 'appeal', 'second_pass', 'second_reject'];
    if (!in_array($log['action'] ?? '', $validActions)) {
        fail("日志 action 非法: " . ($log['action'] ?? 'null'));
    } else {
        pass("审核日志正常，action={$log['action']}");
    }
}

// ── 6. 申诉接口（需 first_reject 状态才可测试）────────────
echo "\n【6】POST /api/articles/{id}/appeal (申诉) — 预期 422（非 first_reject 状态）\n";
$appealResp = Http::withToken($token)->post($baseUrl . "/articles/{$articleId}/appeal", [
    'reason' => '测试申诉理由',
]);
echo "HTTP {$appealResp->status()}\n";
$appealBody = json_decode($appealResp->body(), true);
echo json_encode($appealBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
// 非 first_reject 状态应返回 422
if ($appealResp->status() === 422) {
    pass("正确拒绝申诉（非 first_reject 状态）");
} else {
    fail("申诉边界情况 HTTP={$appealResp->status()}，期望 422");
}

// ── 7. 重提接口（需 modify_required 状态才可测试）──────────
echo "\n【7】POST /api/articles/{id}/resubmit (重提) — 预期 422（非 modify_required 状态）\n";
$resubmitResp = Http::withToken($token)->post($baseUrl . "/articles/{$articleId}/resubmit");
echo "HTTP {$resubmitResp->status()}\n";
$resubmitBody = json_decode($resubmitResp->body(), true);
echo json_encode($resubmitBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
if ($resubmitResp->status() === 422) {
    pass("正确拒绝重提（非 modify_required 状态）");
} else {
    fail("重提边界情况 HTTP={$resubmitResp->status()}，期望 422");
}

// ── 8. ULID 验证 ──────────────────────────────────────────
echo "\n【8】验证所有 ID 为 ULID 格式\n";
$idFields = ['id', 'author_id' => $detail['author']['id'] ?? ''];
$validUlid = '/^[0-9A-Za-z]{26}$/';
if (preg_match($validUlid, $articleId)) {
    pass("article_id 是 ULID: {$articleId}");
} else {
    fail("article_id 不是 ULID: {$articleId}");
}

// ── 结果 ───────────────────────────────────────────────────
echo "\n";
if ($allPassed) {
    echo "=== ✅ M2 审核流程全部通过 ===\n";
} else {
    echo "=== ⚠️ M2 审核流程部分失败 ===\n";
    exit(1);
}
