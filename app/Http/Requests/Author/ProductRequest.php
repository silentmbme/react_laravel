<?php

namespace App\Http\Requests\Author;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'category_id' => ['required', 'exists:categories,id'], 
            'short_description' => ['required'],
            'description' => ['required'],
            'thumbnail' => ['required'],
            'preview_images' => ['required', 'array'],
            'file' => ['required'],
            'version' => ['required'],
            'demo_url' => ['required', 'url'],
            'status' => ['required'], 
            'licenses' => ['required', 'array'],
        ];
    }
}
