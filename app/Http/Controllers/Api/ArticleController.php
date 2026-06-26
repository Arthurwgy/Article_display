<?php

namespace App\Http\Controllers\Api;

use App\Enums\ArticleStatus;
use App\Enums\ReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleDetailResource;
use App\Http\Resources\ArticleListResource;
use App\Http\Resources\ReviewLogResource;
use App\Models\Article;
use App\Models\ArticleReviewLog;
use App\Models\ArticleTag;
use App\Services\ArticleSnapshotService;
use App\Services\ArticleStateMachine;
use App\Services\SensitiveWordChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function __construct(
        private readonly SensitiveWordChecker $sensitiveWordChecker,
        private readonly ArticleStateMachine $stateMachine,
        private readonly ArticleSnapshotService $snapshotService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Article::query()
            ->with(['user', 'category', 'tags']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('name', $request->tag));
        }

        if ($request->filled('author_id')) {
            $query->where('user_id', $request->author_id);
        }

        $status = $request->input('status', 'published');
        if ($status === 'all') {
            $query->whereIn('status', ['published', 'draft']);
        } else {
            $query->where('status', $status);
        }

        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$keyword}%")
                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$keyword}%"))
            );
        }

        $sort = $request->input('sort', 'latest');
        if ($sort === 'hot') {
            $query->orderByDesc('view_count');
        } else {
            $query->orderByDesc('published_at')->orderByDesc('id');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $articles = $query->paginate($perPage);

        return response()->json([
            'data' => ArticleListResource::collection($articles->items()),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $article = Article::with(['user', 'category', 'tags'])
            ->findOrFail($id);

        $article->increment('view_count');

        return response()->json(new ArticleDetailResource($article));
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $article = new Article();
        $article->user_id = $user->id;
        $article->title = $data['title'];
        $article->content = $data['content'];
        $article->category_id = $data['category_id'] ?? null;
        $article->cover_image = $data['cover_image'] ?? null;
        $article->price_gold = $data['price_gold'] ?? 0;
        $article->status = $data['status'] ?? 'draft';
        if ($article->status === 'published') {
            $article->published_at = now();
        }
        $article->slug = $this->generateUniqueSlug($data['title']);
        $article->view_count = 0;
        $article->review_count = 0;
        $article->save();

        $this->syncTags($article, $data['tags'] ?? []);

        $article->load(['user', 'category', 'tags']);

        return response()->json(new ArticleDetailResource($article), 201);
    }

    public function update(UpdateArticleRequest $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $user = $request->user();

        if ($article->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        if (isset($data['title']) && $data['title'] !== $article->title) {
            $data['slug'] = $this->generateUniqueSlug($data['title']);
        }

        $wasPublished = $article->status === 'published';

        $article->fill($data);

        if ($wasPublished && isset($data['content'])) {
            $article->status = 'pending';
            $article->review_count = 0;
        }

        $article->save();

        if (isset($data['tags'])) {
            $this->syncTags($article, $data['tags']);
        }

        $article->load(['user', 'category', 'tags']);

        return response()->json(new ArticleDetailResource($article));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $user = $request->user();

        if ($article->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($article->status !== 'draft') {
            return response()->json(['message' => '只有草稿状态的文章可以删除'], 403);
        }

        $article->delete();

        return response()->json(null, 204);
    }

    /**
     * 提交审核 / 重新提交
     *
     * 触发机审：
     * - none/light：mask 后进入 pending，写 submit log
     * - moderate/severe：进入 first_reject，写 auto_reject log（含命中词）
     *
     * modify_required 重提交：先快照，再走机审
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $user = $request->user();

        if ($article->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $currentStatus = ArticleStatus::tryFrom($article->status);

        // 草稿提交
        if ($currentStatus === ArticleStatus::DRAFT) {
            return $this->doSubmit($article, $user);
        }

        // modify_required 重提交
        if ($currentStatus === ArticleStatus::MODIFY_REQUIRED) {
            return $this->doResubmit($article, $user);
        }

        return response()->json(['message' => '当前状态不允许提交审核'], 422);
    }

    /**
     * 申诉：first_reject → appealing
     */
    public function appeal(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $user = $request->user();

        if ($article->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $currentStatus = ArticleStatus::tryFrom($article->status);
        if ($currentStatus !== ArticleStatus::FIRST_REJECT) {
            return response()->json(['message' => '只有一审驳回状态可以申诉'], 422);
        }

        $to = ArticleStatus::APPEALING;
        if (! $this->stateMachine->canTransition($currentStatus, $to)) {
            return response()->json(['message' => '状态不允许此操作'], 422);
        }

        $article->status = $to->value;
        $article->save();

        $this->logAction($article, $user->id, ReviewAction::APPEAL);

        return response()->json([
            'id' => $article->id,
            'status' => $article->status,
            'message' => '申诉已提交',
        ]);
    }

    /**
     * 修改后重新提交（由 submit 内部调用，也作为独立路由暴露）
     */
    public function resubmit(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $user = $request->user();

        if ($article->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $currentStatus = ArticleStatus::tryFrom($article->status);
        if ($currentStatus !== ArticleStatus::MODIFY_REQUIRED) {
            return response()->json(['message' => '只有需修改状态可以重新提交'], 422);
        }

        return $this->doResubmit($article, $user);
    }

    /**
     * 查看审核日志
     */
    public function reviewLogs(Request $request, string $id): JsonResponse
    {
        $article = Article::findOrFail($id);
        $user = $request->user();

        if ($article->user_id !== $user->id && ! $user->isAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $logs = ArticleReviewLog::where('article_id', $article->id)
            ->with('reviewer:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => ReviewLogResource::collection($logs),
        ]);
    }

    /* ── 内部方法 ── */

    private function doSubmit(Article $article, $user): JsonResponse
    {
        // modify_required 重提交：先快照
        if ($article->review_count > 0 && $article->status === 'modify_required') {
            $this->snapshotService->saveSnapshot($article, $article->review_count);
        }

        $result = $this->runAutoReview($article, $user);

        if ($result['level'] === 'none') {
            $article->status = ArticleStatus::PENDING->value;
            $article->review_count = $article->review_count + 1;
            $article->last_review_at = now();
            $article->save();

            $this->logAction($article, $user->id, ReviewAction::SUBMIT);

            return response()->json([
                'id' => $article->id,
                'status' => $article->status,
                'message' => '提交成功，待审核',
            ]);
        }

        // light：mask 内容后进入 pending
        if ($result['level'] === 'light') {
            $article->content = $this->sensitiveWordChecker->mask($article->content);
            $article->status = ArticleStatus::PENDING->value;
            $article->review_count = $article->review_count + 1;
            $article->last_review_at = now();
            $article->save();

            $this->logAction($article, $user->id, ReviewAction::AUTO_REJECT, '机审替换轻度敏感词后进入待审核');

            return response()->json([
                'id' => $article->id,
                'status' => $article->status,
                'message' => '内容含轻度敏感词，已自动处理，待审核',
            ]);
        }

        // moderate/severe：直接驳回
        $article->status = ArticleStatus::FIRST_REJECT->value;
        $article->review_count = $article->review_count + 1;
        $article->last_review_at = now();
        $article->save();

        $hitWords = array_column($result['matches'], 'word');
        $this->logAction($article, $user->id, ReviewAction::AUTO_REJECT, '机审驳回：含'.count($hitWords).'个敏感词', $hitWords);

        return response()->json([
            'id' => $article->id,
            'status' => $article->status,
            'message' => '内容审核未通过，请修改后重试',
        ], 422);
    }

    private function doResubmit(Article $article, $user): JsonResponse
    {
        // 先快照（当前内容）
        $this->snapshotService->saveSnapshot($article, $article->review_count + 1);

        return $this->doSubmit($article, $user);
    }

    private function runAutoReview(Article $article, $user): array
    {
        return $this->sensitiveWordChecker->check($article->content);
    }

    private function logAction(
        Article $article,
        ?string $reviewerId,
        ReviewAction $action,
        ?string $reason = null,
        ?array $sensitiveWordHit = null,
    ): ArticleReviewLog {
        return ArticleReviewLog::create([
            'article_id' => $article->id,
            'reviewer_id' => $reviewerId,
            'action' => $action->value,
            'reason' => $reason,
            'review_round' => $article->review_count,
            'sensitive_word_hit' => $sensitiveWordHit,
            'created_at' => now(),
        ]);
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (Article::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::random(4);
            $i++;
            if ($i > 10) {
                $slug = $base . '-' . Str::random(8);
                break;
            }
        }

        return $slug;
    }

    private function syncTags(Article $article, array $tagNames): void
    {
        $tagIds = [];

        foreach ($tagNames as $name) {
            $name = trim($name);
            if (! $name) {
                continue;
            }

            $tag = ArticleTag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'use_count' => 0]
            );

            if ($tag->wasRecentlyCreated) {
                $tag->use_count = 1;
                $tag->save();
            } else {
                $tag->increment('use_count');
            }

            $tagIds[] = $tag->id;
        }

        $article->tags()->sync($tagIds);
    }
}
