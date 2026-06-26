<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
            'category_id' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'cover_image' => 'nullable|string|max:500',
            'price_gold' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:draft,pending,first_pass,published,first_reject,modify_required,appealing,second_pass,second_reject',
        ];
    }
}
