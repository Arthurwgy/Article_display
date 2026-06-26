<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleSnapshot extends Model
{
    use HasUlids;
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'review_round',
        'title_snapshot',
        'content_snapshot',
        'cover_image_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
