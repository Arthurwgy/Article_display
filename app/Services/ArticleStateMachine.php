<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ArticleStatus;

class ArticleStateMachine
{
    private const TRANSITIONS = [
        // from => [to => allowed actions]
        'draft' => [
            'pending' => ['submit'],
        ],
        'pending' => [
            'published' => ['first_pass'],
            'first_reject' => ['first_reject'],
            'modify_required' => ['modify_required'],
        ],
        'first_reject' => [
            'appealing' => ['appeal'],
            'modify_required' => ['modify_required'],
        ],
        'modify_required' => [
            'pending' => ['submit'],
        ],
        'appealing' => [
            'published' => ['second_pass'],
            'second_reject' => ['second_reject'],
        ],
        'published' => [
            'unlisted' => ['unlist'],
        ],
        'unlisted' => [
            'pending' => ['republish'],
        ],
    ];

    public function canTransition(ArticleStatus $from, ArticleStatus $to): bool
    {
        $fromKey = $from->value;
        $toKey = $to->value;

        if (! isset(self::TRANSITIONS[$fromKey])) {
            return false;
        }

        return isset(self::TRANSITIONS[$fromKey][$toKey]);
    }

    public function getAvailableTransitions(ArticleStatus $status): array
    {
        $key = $status->value;

        if (! isset(self::TRANSITIONS[$key])) {
            return [];
        }

        return array_keys(self::TRANSITIONS[$key]);
    }

    public function transition(ArticleStatus $from, ArticleStatus $to): bool
    {
        if (! $this->canTransition($from, $to)) {
            return false;
        }

        return true;
    }
}
