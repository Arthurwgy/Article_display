<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'cover_image' => $this->cover_image,
            'is_top' => $this->is_top,
            'is_featured' => $this->is_featured,
            'is_paid' => $this->price_gold > 0,
            'price_gold' => (int) $this->price_gold,
            'view_count' => (int) $this->view_count,
            'review_count' => (int) $this->review_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->toArray()),
        ];
    }
}
