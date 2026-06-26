<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticleTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ArticleTag::query();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        $tags = $query
            ->orderByDesc('use_count')
            ->limit(50)
            ->get(['id', 'name', 'slug', 'use_count']);

        return response()->json([
            'data' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'use_count' => $tag->use_count,
            ]),
        ]);
    }
}
