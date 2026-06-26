<?php

declare(strict_types=1);

namespace App\Services;

class SensitiveWordChecker
{
    private array $words = [];

    public function __construct()
    {
        $this->words = config('sensitive_words.words', []);
    }

    /**
     * @return array{level: string, matches: array}
     */
    public function check(string $content): array
    {
        if (empty($this->words) || empty(trim($content))) {
            return ['level' => 'none', 'matches' => []];
        }

        $matches = [];
        $severeMatches = [];
        $moderateMatches = [];
        $lightMatches = [];

        foreach ($this->words as $word => $level) {
            if (mb_strpos($content, $word) !== false) {
                $matchEntry = ['word' => $word, 'level' => $level];
                $matches[] = $matchEntry;

                match ($level) {
                    'severe' => $severeMatches[] = $word,
                    'moderate' => $moderateMatches[] = $word,
                    'light' => $lightMatches[] = $word,
                };
            }
        }

        if (! empty($severeMatches)) {
            return ['level' => 'severe', 'matches' => $matches];
        }
        if (! empty($moderateMatches)) {
            return ['level' => 'moderate', 'matches' => $matches];
        }
        if (! empty($lightMatches)) {
            return ['level' => 'light', 'matches' => $matches];
        }

        return ['level' => 'none', 'matches' => []];
    }

    public function mask(string $content): string
    {
        foreach ($this->words as $word => $level) {
            if ($level === 'light') {
                $content = str_replace($word, '***', $content);
            }
        }

        return $content;
    }
}
