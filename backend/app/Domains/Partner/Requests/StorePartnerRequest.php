<?php

namespace App\Domains\Partner\Requests;

use App\Domains\Partner\Enums\PartnerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Alle dürfen Partner erstellen
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::enum(PartnerStatus::class)],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'custom_fields' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            
            // Primary contact (optional)
            'primary_contact.name' => ['nullable', 'required_with:primary_contact', 'string', 'max:255'],
            'primary_contact.position' => ['nullable', 'string', 'max:255'],
            'primary_contact.email' => ['nullable', 'email', 'max:255'],
            'primary_contact.phone' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Eine Kategorie muss ausgewählt werden.',
            'category_id.exists' => 'Die ausgewählte Kategorie existiert nicht.',
            'name.required' => 'Der Name ist erforderlich.',
            'name.max' => 'Der Name darf maximal 255 Zeichen lang sein.',
            'website.url' => 'Die Website muss eine gültige URL sein.',
            'email.email' => 'Die E-Mail-Adresse muss gültig sein.',
            'primary_contact.name.required_with' => 'Der Name des Ansprechpartners ist erforderlich.',
        ];
    }
}