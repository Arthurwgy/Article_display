<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ArticleTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'use_count',
    ];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            Article::class,
            'article_tag',
            'tag_id',
            'article_id'
        )->withPivot('created_at');
    }
}
