<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleReviewLog extends Model
{
    use HasUlids;
    public $timestamps = false;

    protected $fillable = [
        'article_id',
        'reviewer_id',
        'action',
        'reason',
        'review_round',
        'sensitive_word_hit',
        'created_at',
    ];

    protected $casts = [
        'sensitive_word_hit' => 'array',
        'review_round' => 'integer',
        'created_at' => 'datetime',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
