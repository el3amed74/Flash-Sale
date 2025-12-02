<?php

namespace App\Http\Requests;

use App\DTOs\CreateOrderDTO;
use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hold_id' => ['required', 'integer', 'exists:holds,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'hold_id.required' => 'Hold ID is required',
            'hold_id.exists' => 'Hold not found',
        ];
    }

    public function toDTO(): CreateOrderDTO
    {
        return new CreateOrderDTO(
            holdId: $this->input('hold_id')
        );
    }
}
