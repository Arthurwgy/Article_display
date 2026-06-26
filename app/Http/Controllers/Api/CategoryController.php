<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ArticleCategory::with(['children'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $result = $categories->map(fn ($cat) => $this->formatCategory($cat));

        return response()->json(['data' => $result]);
    }

    private function formatCategory(ArticleCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'sort_order' => $category->sort_order,
            'children' => $category->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
                'sort_order' => $child->sort_order,
            ])->toArray(),
        ];
    }
}
