<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewLogResource\Pages;
use App\Models\ArticleReviewLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewLogResource extends Resource
{
    protected static ?string $model = ArticleReviewLog::class;

    protected static ?string $navigationLabel = '审核日志';

    protected static ?string $modelLabel = '审核日志';

    protected static ?string $pluralModelLabel = '审核日志';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return '审计';
    }

    // 只读资源（执行方案 3.7）
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['article', 'reviewer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('article.title')
                    ->label('文章')
                    ->limit(40)
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('reviewer.name')
                    ->label('审核人')
                    ->placeholder('系统')
                    ->default('系统')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('动作')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'submit', 'first_pass', 'second_pass' => 'success',
                        'first_reject', 'second_reject', 'auto_reject' => 'danger',
                        'modify_required' => 'warning',
                        'appeal' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => \App\Enums\ReviewAction::tryFrom($state)?->label() ?? $state),
                TextColumn::make('review_round')
                    ->label('轮次')
                    ->numeric(),
                TextColumn::make('reason')
                    ->label('理由')
                    ->limit(80)
                    ->placeholder('—'),
                TextColumn::make('sensitive_word_hit')
                    ->label('命中敏感词')
                    ->formatStateUsing(function ($state) {
                        if (! is_array($state) || empty($state)) {
                            return '—';
                        }
                        return implode(', ', array_map(
                            fn ($entry) => is_array($entry)
                                ? ($entry['word'] ?? json_encode($entry))
                                : (string) $entry,
                            $state
                        ));
                    })
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('动作')
                    ->options([
                        'submit' => '提交审核',
                        'auto_reject' => '机审驳回',
                        'first_pass' => '一审通过',
                        'first_reject' => '一审驳回',
                        'modify_required' => '需修改',
                        'appeal' => '申诉',
                        'second_pass' => '二审通过',
                        'second_reject' => '二审驳回',
                    ]),
                SelectFilter::make('article_id')
                    ->label('文章')
                    ->relationship('article', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('reviewer_id')
                    ->label('审核人')
                    ->relationship('reviewer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('review_round')
                    ->label('轮次')
                    ->options([
                        1 => '第一轮',
                        2 => '第二轮',
                    ]),
                Filter::make('created_at')
                    ->label('时间范围')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('从')
                            ->displayFormat('Y/m/d')
                            ->default(now()->toDateString()),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('至')
                            ->displayFormat('Y/m/d')
                            ->default(now()->toDateString()),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->searchPlaceholder('搜索文章 / 审核人...');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviewLogs::route('/'),
        ];
    }
}