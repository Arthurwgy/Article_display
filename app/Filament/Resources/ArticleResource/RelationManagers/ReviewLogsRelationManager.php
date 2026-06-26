<?php

declare(strict_types=1);

namespace App\Filament\Resources\ArticleResource\RelationManagers;

use App\Models\ArticleReviewLog;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviewLogs';

    protected static ?string $title = '审核记录';

    protected static ?string $modelLabel = '审核日志';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('reviewer.name')
                    ->label('审核人')
                    ->placeholder('系统')
                    ->default('系统'),
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
                    ->limit(60)
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
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}