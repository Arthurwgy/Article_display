<?php

namespace App\Http\Resources;

use App\Services\MarkdownRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isPaid = $this->price_gold > 0;
        $isOwner = $request->user() && $request->user()->id === $this->user_id;
        $hasPurchased = false;

        if ($isPaid && $isOwner) {
            $hasPurchased = true;
        }

        if ($isPaid && ! $hasPurchased) {
            return [
                'id' => $this->id,
                'title' => $this->title,
                'slug' => $this->slug,
                'cover_image' => $this->cover_image,
                'status' => $this->status,
                'is_top' => $this->is_top,
                'is_featured' => $this->is_featured,
                'is_paid' => true,
                'price_gold' => (int) $this->price_gold,
                'is_paid_by_me' => false,
                'view_count' => (int) $this->view_count,
                'review_count' => (int) $this->review_count,
                'published_at' => $this->published_at?->toIso8601String(),
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
                'author' => $this->whenLoaded('user', fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                    'bio' => $this->user->bio,
                ]),
                'category' => $this->whenLoaded('category', fn () => [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ]),
                'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->toArray()),
            ];
        }

        $renderer = app(MarkdownRenderer::class);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'content_html' => $renderer->render($this->content),
            'cover_image' => $this->cover_image,
            'status' => $this->status,
            'is_top' => $this->is_top,
            'is_featured' => $this->is_featured,
            'is_paid' => $isPaid,
            'price_gold' => (int) $this->price_gold,
            'is_paid_by_me' => $hasPurchased,
            'view_count' => (int) $this->view_count,
            'review_count' => (int) $this->review_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'author' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
                'bio' => $this->user->bio,
            ]),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')->toArray()),
        ];
    }
}
