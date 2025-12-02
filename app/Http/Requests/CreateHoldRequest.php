<?php

namespace App\Http\Requests;

use App\DTOs\CreateHoldDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateHoldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'qty' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product ID is required',
            'product_id.exists' => 'Product not found',
            'qty.required' => 'Quantity is required',
            'qty.min' => 'Quantity must be at least 1',
        ];
    }

    public function toDTO(): CreateHoldDTO
    {
        return new CreateHoldDTO(
            productId: $this->input('product_id'),
            qty: $this->input('qty'),
            idempotencyKey: $this->input('idempotency_key')
        );
    }
}

