<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\ArticleCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = ArticleCategory::class;

    protected static ?string $navigationLabel = '分类';

    protected static ?string $modelLabel = '分类';

    protected static ?string $pluralModelLabel = '分类';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return '分类体系';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('分类名')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('parent_id')
                ->label('上级分类')
                ->relationship('parent', 'name')
                ->searchable()
                ->preload()
                ->placeholder('顶级分类'),
            TextInput::make('sort_order')
                ->label('排序')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('name')
                    ->label('分类名')
                    ->searchable()
                    ->formatStateUsing(function (ArticleCategory $record): string {
                        $depth = 0;
                        $node = $record;
                        while ($node->parent_id !== null && $depth < 5) {
                            $node = $node->parent;
                            if (! $node) {
                                break;
                            }
                            $depth++;
                        }
                        return str_repeat('— ', $depth) . $record->name;
                    }),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                TextColumn::make('parent.name')
                    ->label('上级分类')
                    ->placeholder('顶级'),
                TextColumn::make('sort_order')
                    ->label('排序')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i'),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label('编辑'),
                \Filament\Actions\DeleteAction::make()
                    ->label('删除')
                    ->before(function (ArticleCategory $record) {
                        // 有子分类时禁止删除
                        if ($record->children()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('无法删除')
                                ->body('该分类下存在子分类，请先删除子分类。')
                                ->send();
                            throw new \Filament\Support\Exceptions\Halt;
                        }
                    }),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make()->label('新增分类'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
        ];
    }
}