<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Authorization is handled by PostPolicy via authorizeResource() in PostController.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:200'],
            'slug'         => ['required', 'string', 'max:255', 'unique:posts,slug'],
            'body'         => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
