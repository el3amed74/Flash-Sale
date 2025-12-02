<?php

namespace App\Http\Requests;

use App\DTOs\PaymentWebhookDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:255'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'status' => ['required', 'string', Rule::in(['success', 'paid', 'failed', 'cancelled'])],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'Idempotency key is required',
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of: success, paid, failed, cancelled',
        ];
    }
    public function toDTO(): PaymentWebhookDTO
    {
        return new PaymentWebhookDTO(
            idempotencyKey: $this->input('idempotency_key'),
            orderId: $this->input('order_id'),
            status: $this->input('status'),
            paymentReference: $this->input('payment_reference'),
            provider: $this->input('provider'),
        );
    }
}
