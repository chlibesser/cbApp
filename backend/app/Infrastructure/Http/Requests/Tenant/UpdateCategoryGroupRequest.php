<?php

namespace App\Infrastructure\Http\Requests\Tenant;

use App\Core\Category\Enums\SelectionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'selection_type' => ['sometimes', Rule::enum(SelectionType::class)],
            'is_required' => ['boolean'],
            'is_active' => ['boolean'],
            'ai_enabled' => ['boolean'],
            'ai_prompt_context' => ['nullable', 'string', 'max:1000'],
            'ai_confidence_threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Der Name der Kategorie-Gruppe ist erforderlich.',
            'name.max' => 'Der Name darf maximal 100 Zeichen lang sein.',
            'selection_type.enum' => 'Der Auswahltyp ist ungültig.',
            'ai_confidence_threshold.min' => 'Der Vertrauensschwellenwert muss mindestens 0 sein.',
            'ai_confidence_threshold.max' => 'Der Vertrauensschwellenwert darf maximal 1 sein.',
        ];
    }
}