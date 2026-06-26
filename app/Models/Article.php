<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes, HasUlids;

    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'title',
        'slug',
        'content',
        'cover_image',
        'is_top',
        'is_featured',
        'price_gold',
        'view_count',
        'review_count',
        'last_review_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_top' => 'boolean',
            'is_featured' => 'boolean',
            'price_gold' => 'integer',
            'view_count' => 'integer',
            'review_count' => 'integer',
            'last_review_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ArticleTag::class,
            'article_tag',
            'article_id',
            'tag_id'
        )->withPivot('created_at');
    }

    public function reviewLogs(): HasMany
    {
        return $this->hasMany(ArticleReviewLog::class)->orderByDesc('created_at');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
