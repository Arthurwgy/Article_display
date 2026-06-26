<?php

declare(strict_types=1);

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Enums\ArticleStatus;
use App\Enums\ReviewAction;
use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use App\Models\ArticleReviewLog;
use App\Services\ArticleSnapshotService;
use App\Services\ArticleStateMachine;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected ?string $heading = '文章详情与审核';

    /**
     * 编辑页 infolist（只读展示）。
     */
    public function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('基本信息')
                ->columns(2)
                ->schema([
                    TextEntry::make('title')->label('标题'),
                    TextEntry::make('slug')->label('Slug'),
                    ImageEntry::make('cover_image')->label('封面图')->placeholder('无'),
                    TextEntry::make('category.name')->label('分类')->placeholder('—'),
                    TextEntry::make('tags.name')
                        ->label('标签')
                        ->badge()
                        ->separator(',')
                        ->placeholder('—'),
                    TextEntry::make('status')
                        ->label('状态')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'draft' => 'gray',
                            'pending' => 'info',
                            'first_pass' => 'cyan',
                            'published' => 'success',
                            'first_reject' => 'danger',
                            'modify_required' => 'warning',
                            'appealing' => 'purple',
                            'second_pass' => 'success',
                            'second_reject' => 'danger',
                            'unlisted' => 'gray',
                            default => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => ArticleStatus::tryFrom($state)?->label() ?? $state),
                    TextEntry::make('is_top')->label('置顶')->formatStateUsing(fn ($state) => $state ? '是' : '否'),
                    TextEntry::make('is_featured')->label('精选')->formatStateUsing(fn ($state) => $state ? '是' : '否'),
                    TextEntry::make('price_gold')->label('付费价格（金币）')->placeholder('免费'),
                    TextEntry::make('view_count')->label('浏览量')->numeric(),
                ]),
            \Filament\Schemas\Components\Section::make('正文内容')
                ->schema([
                    TextEntry::make('content')
                        ->label('正文（Markdown 预览）')
                        ->markdown()
                        ->prose()
                        ->columnSpanFull(),
                ]),
            \Filament\Schemas\Components\Section::make('作者与时间')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.name')->label('作者昵称'),
                    TextEntry::make('user.email')->label('作者邮箱'),
                    TextEntry::make('created_at')->label('创建时间')->dateTime('Y-m-d H:i'),
                    TextEntry::make('last_review_at')->label('最后审核时间')->dateTime('Y-m-d H:i')->placeholder('—'),
                    TextEntry::make('published_at')->label('发布时间')->dateTime('Y-m-d H:i')->placeholder('—'),
                ]),
        ]);
    }

    /**
     * 顶部动作：按当前 status 动态显示审核/上下架/强制删除。
     * 表格行 Action 不暴露审核动作（D24），全部从编辑页触发。
     */
    protected function getHeaderActions(): array
    {
        $actions = [];
        /** @var Article $article */
        $article = $this->getRecord();
        $status = ArticleStatus::tryFrom((string) $article->status);

        if ($status === ArticleStatus::PENDING) {
            $actions[] = $this->firstPassAction();
            $actions[] = $this->firstRejectAction();
            $actions[] = $this->modifyRequiredAction();
        }

        if ($status === ArticleStatus::FIRST_REJECT) {
            // 作者可对 first_reject 发起 appeal 进入 appealing；管理员此时不需操作
            // 但 D24 要求所有审核动作下沉到编辑页，这里保留一个「恢复可申诉」提示
            $actions[] = Action::make('note_appeal')
                ->label('等待作者申诉')
                ->disabled()
                ->color('gray');
        }

        if ($status === ArticleStatus::MODIFY_REQUIRED) {
            $actions[] = Action::make('note_resubmit')
                ->label('等待作者重提')
                ->disabled()
                ->color('gray');
        }

        if ($status === ArticleStatus::APPEALING) {
            $actions[] = $this->secondPassAction();
            $actions[] = $this->secondRejectAction();
        }

        if ($status === ArticleStatus::PUBLISHED) {
            $actions[] = $this->unlistAction();
        }

        if ($status === ArticleStatus::UNLISTED) {
            $actions[] = $this->republishAction();
        }

        if ($status === ArticleStatus::DRAFT) {
            $actions[] = $this->forceDeleteAction();
        }

        return $actions;
    }

    protected function firstPassAction(): Action
    {
        return Action::make('first_pass')
            ->label('一审通过')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading('确认一审通过')
            ->modalDescription('通过后 status → published，published_at 自动写入当前时间。')
            ->action(function () {
                $this->applyTransition(
                    ArticleStatus::PUBLISHED,
                    ReviewAction::FIRST_PASS,
                    null,
                    null,
                    writePublishedAt: true
                );
            });
    }

    protected function firstRejectAction(): Action
    {
        return Action::make('first_reject')
            ->label('一审驳回')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->form([
                Textarea::make('reason')
                    ->label('驳回理由')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data) {
                $this->applyTransition(
                    ArticleStatus::FIRST_REJECT,
                    ReviewAction::FIRST_REJECT,
                    $data['reason'],
                    null
                );
            });
    }

    protected function modifyRequiredAction(): Action
    {
        return Action::make('modify_required')
            ->label('要求修改')
            ->color('warning')
            ->icon('heroicon-o-pencil-square')
            ->form([
                Textarea::make('reason')
                    ->label('修改要求')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data) {
                $this->applyTransition(
                    ArticleStatus::MODIFY_REQUIRED,
                    ReviewAction::MODIFY_REQUIRED,
                    $data['reason'],
                    null,
                    createSnapshot: true
                );
            });
    }

    protected function secondPassAction(): Action
    {
        return Action::make('second_pass')
            ->label('二审通过')
            ->color('success')
            ->icon('heroicon-o-check-circle')
            ->requiresConfirmation()
            ->modalHeading('确认二审通过')
            ->action(function () {
                $this->applyTransition(
                    ArticleStatus::PUBLISHED,
                    ReviewAction::SECOND_PASS,
                    null,
                    null,
                    writePublishedAt: true
                );
            });
    }

    protected function secondRejectAction(): Action
    {
        return Action::make('second_reject')
            ->label('二审驳回')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->form([
                Textarea::make('reason')
                    ->label('驳回理由')
                    ->required()
                    ->rows(3),
            ])
            ->action(function (array $data) {
                $this->applyTransition(
                    ArticleStatus::SECOND_REJECT,
                    ReviewAction::SECOND_REJECT,
                    $data['reason'],
                    null
                );
            });
    }

    protected function unlistAction(): Action
    {
        return Action::make('unlist')
            ->label('下架')
            ->color('gray')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->requiresConfirmation()
            ->modalHeading('确认下架')
            ->modalDescription('文章将从公开列表移除，状态变为 unlisted，可后续恢复。')
            ->action(function () {
                $this->applyTransition(
                    ArticleStatus::UNLISTED,
                    ReviewAction::FIRST_REJECT, // 不属于审核日志，但必须有值；用 SUBMIT 作为中性事件
                    null,
                    null,
                    reviewActionOverride: ReviewAction::SUBMIT
                );
            });
    }

    protected function republishAction(): Action
    {
        return Action::make('republish')
            ->label('恢复上架')
            ->color('info')
            ->icon('heroicon-o-arrow-uturn-up')
            ->requiresConfirmation()
            ->modalHeading('确认恢复上架')
            ->modalDescription('status → pending，需重新走审核流程。')
            ->action(function () {
                $this->applyTransition(
                    ArticleStatus::PENDING,
                    ReviewAction::SUBMIT,
                    null,
                    null
                );
            });
    }

    protected function forceDeleteAction(): Action
    {
        return Action::make('force_delete')
            ->label('永久删除')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('确认永久删除')
            ->modalDescription('草稿状态文章将从数据库永久删除，操作不可逆。')
            ->action(function () {
                $article = $this->getRecord();
                $article->forceDelete();
                Notification::make()->success()->title('已永久删除')->send();
                $this->redirect($this->getResourceUrl('index'));
            });
    }

    /**
     * 统一的状态过渡执行器：
     * - 检查 ArticleStateMachine 是否允许该过渡
     * - 写 article_review_logs（含 sensitive_word_hit 默认 null）
     * - 可选调用 ArticleSnapshotService::saveSnapshot
     * - 可选写 published_at
     */
    protected function applyTransition(
        ArticleStatus $to,
        ReviewAction $reviewAction,
        ?string $reason,
        ?array $sensitiveWordHit,
        bool $writePublishedAt = false,
        bool $createSnapshot = false,
        ?ReviewAction $reviewActionOverride = null,
    ): void {
        /** @var Article $article */
        $article = $this->getRecord();
        $from = ArticleStatus::tryFrom((string) $article->status);

        if (! $from) {
            Notification::make()->danger()->title('当前状态非法')->send();
            return;
        }

        $sm = app(ArticleStateMachine::class);
        if (! $sm->canTransition($from, $to)) {
            Notification::make()
                ->danger()
                ->title("不允许的状态过渡: {$from->value} → {$to->value}")
                ->send();
            return;
        }

        $article->status = $to->value;

        if ($writePublishedAt && $to === ArticleStatus::PUBLISHED) {
            $article->published_at = now();
        }

        $article->review_count = ($article->review_count ?? 0) + 1;
        $article->last_review_at = now();
        $article->save();

        ArticleReviewLog::create([
            'article_id' => $article->id,
            'reviewer_id' => Auth::id(),
            'action' => ($reviewActionOverride ?? $reviewAction)->value,
            'reason' => $reason,
            'review_round' => $article->review_count,
            'sensitive_word_hit' => $sensitiveWordHit,
            'created_at' => now(),
        ]);

        if ($createSnapshot) {
            app(ArticleSnapshotService::class)->saveSnapshot($article, $article->review_count);
        }

        Notification::make()
            ->success()
            ->title("已更新：{$from->value} → {$to->value}")
            ->send();

        // 刷新当前页面以重新计算 Header Actions
        $this->refresh();
    }
}