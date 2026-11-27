<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable'],
        ];
    }

    /**
     * Get validated data with normalized active/inactive status string.
     *
     * @return array{name: string, description: ?string, status: string}
     */
    public function payload(): array
    {
        $data = $this->validated();
        $data['status'] = $this->boolean('status') ? 'active' : 'inactive';

        return $data;
    }
}
