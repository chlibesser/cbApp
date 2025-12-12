<?php

namespace App\Infrastructure\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'ai_positive_description' => ['nullable', 'string', 'max:1000'],
            'ai_negative_description' => ['nullable', 'string', 'max:1000'],
            'ai_keywords' => ['nullable', 'array'],
            'ai_keywords.*' => ['string', 'max:50'],
            'custom_prompt' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name der Kategorie ist erforderlich.',
            'name.max' => 'Der Name darf maximal 100 Zeichen lang sein.',
            'ai_keywords.*.max' => 'Schlüsselwörter dürfen maximal 50 Zeichen lang sein.',
        ];
    }

    public function prepareForValidation(): void
    {
        // Convert comma-separated keywords to array
        if ($this->has('ai_keywords') && is_string($this->ai_keywords)) {
            $this->merge([
                'ai_keywords' => array_map('trim', explode(',', $this->ai_keywords))
            ]);
        }
    }
}