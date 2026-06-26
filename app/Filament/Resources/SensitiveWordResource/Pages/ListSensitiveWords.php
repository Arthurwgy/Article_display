<?php

declare(strict_types=1);

namespace App\Filament\Resources\SensitiveWordResource\Pages;

use App\Filament\Resources\SensitiveWordResource;
use App\Models\SensitiveWord;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Illuminate\Support\Facades\Storage;

class ListSensitiveWords extends ListRecords
{
    protected static string $resource = SensitiveWordResource::class;

    protected static ?string $breadcrumb = '敏感词';

    protected function getHeaderActions(): array
    {
        return [
            $this->importAction(),
            $this->exportAction(),
        ];
    }

    /**
     * 导入：上传 .txt → 按空白符拆分 token → upsert（D21/D22/D25）
     */
    protected function importAction(): Action
    {
        return Action::make('import')
            ->label('批量导入')
            ->color('primary')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('file')
                    ->label('敏感词文件 (.txt)')
                    ->disk('local')
                    ->directory('temp-imports')
                    ->acceptedFileTypes(['text/plain'])
                    ->required(),
                Select::make('level')
                    ->label('级别')
                    ->options([
                        'light' => '轻度',
                        'moderate' => '中度',
                        'severe' => '重度',
                    ])
                    ->required()
                    ->default('moderate'),
                TextInput::make('group_name')
                    ->label('分组（选填）')
                    ->placeholder('选填，不填则为空'),
            ])
            ->action(function (array $data) {
                $path = $data['file'];
                $absolutePath = Storage::disk('local')->path($path);
                $content = file_get_contents($absolutePath);

                // D21: 按空白符拆分
                $tokens = preg_split('/\s+/', (string) $content, -1, PREG_SPLIT_NO_EMPTY);
                $tokens = array_values(array_unique(array_filter(array_map('trim', $tokens), fn ($t) => $t !== '')));

                $existing = SensitiveWord::whereIn('word', $tokens)->pluck('word')->all();
                $toInsert = array_values(array_diff($tokens, $existing));

                $now = now();
                $rows = array_map(fn ($word) => [
                    'word' => $word,
                    'level' => $data['level'],
                    'group_name' => $data['group_name'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $toInsert);

                if (! empty($rows)) {
                    SensitiveWord::insert($rows);
                }

                Storage::disk('local')->delete($path);

                Notification::make()
                    ->success()
                    ->title('导入完成')
                    ->body(sprintf(
                        '共解析 %d 个词，成功导入 %d 个，跳过 %d 个已存在。',
                        count($tokens),
                        count($toInsert),
                        count($existing)
                    ))
                    ->send();
            });
    }

    /**
     * 导出：全表 → 80 词换行空格分隔 → streamDownload
     */
    protected function exportAction(): Action
    {
        return Action::make('export')
            ->label('导出')
            ->color('gray')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function () {
                $words = SensitiveWord::query()->orderBy('id')->pluck('word')->all();
                $lines = array_chunk($words, 80);
                $content = implode("\n", array_map(fn ($chunk) => implode(' ', $chunk), $lines));

                return ResponseFacade::streamDownload(
                    function () use ($content) {
                        echo $content;
                    },
                    'sensitive_words.txt'
                );
            });
    }
}