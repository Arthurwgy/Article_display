<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleSnapshot;

class ArticleSnapshotService
{
    public function saveSnapshot(Article $article, int $reviewRound): void
    {
        ArticleSnapshot::updateOrCreate(
            [
                'article_id' => $article->id,
                'review_round' => $reviewRound,
            ],
            [
                'title_snapshot' => $article->title,
                'content_snapshot' => $article->content,
                'cover_image_snapshot' => $article->cover_image,
            ]
        );
    }

    public function getSnapshot(Article $article, int $reviewRound): ?ArticleSnapshot
    {
        return ArticleSnapshot::where('article_id', $article->id)
            ->where('review_round', $reviewRound)
            ->first();
    }
}
