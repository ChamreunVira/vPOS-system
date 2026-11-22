<?php

namespace App\Http\Requests;

use App\Models\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $enabledMethods = PaymentMethod::where('is_enable', true)->pluck('code')->all();
        if (empty($enabledMethods)) {
            $enabledMethods = config('vpos.payment_methods', ['cash']);
        }

        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],
            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
                'max:999999',
            ],
            'customer_id' => [
                'nullable',
                'integer',
                'exists:customers,id',
            ],
            'discount_percent' => [
                'required',
                'numeric',
                Rule::in(config('vpos.discount_options', [0])),
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in($enabledMethods),
            ],
            'amount_received' => [
                'required_if:payment_method,cash',
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }
}
