<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TagResource\Pages;
use App\Models\ArticleTag;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TagResource extends Resource
{
    protected static ?string $model = ArticleTag::class;

    protected static ?string $navigationLabel = '标签';

    protected static ?string $modelLabel = '标签';

    protected static ?string $pluralModelLabel = '标签';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return '分类体系';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('标签名')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('articles'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')
                    ->label('标签名')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('articles_count')
                    ->label('使用次数')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label('编辑'),
                \Filament\Actions\DeleteAction::make()
                    ->label('删除')
                    ->before(function (ArticleTag $record) {
                        $count = $record->articles()->count();
                        if ($count > 0) {
                            Notification::make()
                                ->danger()
                                ->title('无法删除')
                                ->body("该标签有 {$count} 篇文章正在使用。")
                                ->send();
                            throw new \Filament\Support\Exceptions\Halt;
                        }
                    }),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make()->label('新增标签'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTags::route('/'),
        ];
    }
}