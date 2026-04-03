<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'in:cash,qris'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'items.*.service_name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'payment_method.in' => 'Metode pembayaran harus Cash atau QRIS.',
            'items.required' => 'Minimal satu layanan harus dipilih.',
            'items.min' => 'Minimal satu layanan harus dipilih.',
            'items.*.qty.min' => 'Jumlah minimal 1.',
        ];
    }
}
