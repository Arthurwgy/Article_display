<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ArticleCategory::class, 'parent_id');
    }
}
