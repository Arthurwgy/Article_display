<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ArticleStatus;
use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationLabel = '文章管理';

    protected static ?string $modelLabel = '文章';

    protected static ?string $pluralModelLabel = '文章';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return '内容管理';
    }

    public static function form(Schema $schema): Schema
    {
        // 编辑页可编辑字段：状态（仅 published/unlisted）、管理员备注（复用 reason，见 3.3.4）
        return $schema->components([
            Select::make('status')
                ->label('状态')
                ->options([
                    ArticleStatus::PUBLISHED->value => ArticleStatus::PUBLISHED->label(),
                    ArticleStatus::UNLISTED->value => ArticleStatus::UNLISTED->label(),
                ])
                ->required()
                ->helperText('其他状态由审核动作触发，不在此处手动改'),
            Textarea::make('admin_note')
                ->label('管理员备注')
                ->helperText('当前用于说明文字，最终落库仍写入 article_review_logs.reason（D13/D14 决策）')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'category'])->withCount('reviewLogs'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->limit(10)
                    ->tooltip(fn (Article $record) => $record->id)
                    ->searchable(),
                TextColumn::make('title')
                    ->label('标题')
                    ->limit(40)
                    ->searchable()
                    ->url(fn (Article $record) => ArticleResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('user.name')
                    ->label('作者')
                    ->searchable()
                    ->default('—'),
                TextColumn::make('category.name')
                    ->label('分类')
                    ->default('—'),
                TextColumn::make('status')
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
                TextColumn::make('review_logs_count')
                    ->label('审核轮次')
                    ->numeric()
                    ->default(0),
                TextColumn::make('last_review_at')
                    ->label('最后审核时间')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                // D20：只暴露 8 个稳定态，不列出 first_pass / second_pass
                SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        ArticleStatus::DRAFT->value => ArticleStatus::DRAFT->label(),
                        ArticleStatus::PENDING->value => ArticleStatus::PENDING->label(),
                        ArticleStatus::PUBLISHED->value => ArticleStatus::PUBLISHED->label(),
                        ArticleStatus::FIRST_REJECT->value => ArticleStatus::FIRST_REJECT->label(),
                        ArticleStatus::MODIFY_REQUIRED->value => ArticleStatus::MODIFY_REQUIRED->label(),
                        ArticleStatus::APPEALING->value => ArticleStatus::APPEALING->label(),
                        ArticleStatus::SECOND_REJECT->value => ArticleStatus::SECOND_REJECT->label(),
                        ArticleStatus::UNLISTED->value => ArticleStatus::UNLISTED->label(),
                    ]),
                SelectFilter::make('category_id')
                    ->label('分类')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('提交时间')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')
                            ->label('从')
                            ->displayFormat('Y/m/d')
                            ->default(now()->toDateString()),
                        \Filament\Forms\Components\DatePicker::make('created_until')
                            ->label('至')
                            ->displayFormat('Y/m/d')
                            ->default(now()->toDateString()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('title')
                    ->label('标题关键词')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('value')->label('关键词'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] ?? null, fn (Builder $q, $value) => $q->where('title', 'like', "%{$value}%"));
                    }),
            ])
            ->recordActions([
                // D24：表格行只保留「查看」/「编辑」，审核动作下沉到编辑页 Header Actions
                \Filament\Actions\ViewAction::make()->label('查看详情'),
                \Filament\Actions\EditAction::make()->label('编辑'),
            ])
            ->toolbarActions([])
            ->searchPlaceholder('搜索标题 / 作者 / ID...')
            ->filtersFormMaxHeight('24rem');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReviewLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}