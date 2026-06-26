<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'article_id' => $this->article_id,
            'action' => $this->action,
            'reason' => $this->reason,
            'review_round' => $this->review_round,
            'sensitive_word_hit' => $this->sensitive_word_hit,
            'reviewer' => $this->whenLoaded('reviewer', fn () => [
                'id' => $this->reviewer->id,
                'name' => $this->reviewer->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
