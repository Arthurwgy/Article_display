<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = '用户';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return '用户';
    }

    // 只读资源（执行方案 3.8）
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->limit(15)
                    ->searchable(),
                TextColumn::make('name')
                    ->label('昵称')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('邮箱')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('角色')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'author' => 'info',
                        'reader' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => '管理员',
                        'author' => '作者',
                        'reader' => '读者',
                        default => $state,
                    }),
                TextColumn::make('coin_balance')
                    ->label('硬币余额')
                    ->numeric(),
                TextColumn::make('gold_balance')
                    ->label('金币余额')
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('created_at')
                    ->label('注册时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('最后登录')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—'),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->searchPlaceholder('搜索昵称 / 邮箱 / ID...');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}