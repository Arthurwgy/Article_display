<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SensitiveWordResource\Pages;
use App\Models\SensitiveWord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SensitiveWordResource extends Resource
{
    protected static ?string $model = SensitiveWord::class;

    protected static ?string $navigationLabel = '敏感词';

    protected static ?string $modelLabel = '敏感词';

    protected static ?string $pluralModelLabel = '敏感词';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return '安全';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('word')
                ->label('敏感词')
                ->required()
                ->maxLength(100)
                ->unique(ignoreRecord: true),
            Select::make('level')
                ->label('级别')
                ->options([
                    'light' => '轻度',
                    'moderate' => '中度',
                    'severe' => '重度',
                ])
                ->required(),
            TextInput::make('group_name')
                ->label('分组')
                ->maxLength(50)
                ->placeholder('选填'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('word')
                    ->label('敏感词')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('level')
                    ->label('级别')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'light' => 'warning',
                        'moderate' => 'orange',
                        'severe' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'light' => '轻度',
                        'moderate' => '中度',
                        'severe' => '重度',
                        default => $state,
                    }),
                TextColumn::make('group_name')
                    ->label('分组')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('级别')
                    ->options([
                        'light' => '轻度',
                        'moderate' => '中度',
                        'severe' => '重度',
                    ]),
                SelectFilter::make('group_name')
                    ->label('分组')
                    ->options(fn () => SensitiveWord::query()
                        ->whereNotNull('group_name')
                        ->where('group_name', '!=', '')
                        ->distinct()
                        ->pluck('group_name', 'group_name')
                        ->filter()
                        ->toArray()
                    ),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label('编辑'),
                \Filament\Actions\DeleteAction::make()->label('删除'),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSensitiveWords::route('/'),
        ];
    }
}