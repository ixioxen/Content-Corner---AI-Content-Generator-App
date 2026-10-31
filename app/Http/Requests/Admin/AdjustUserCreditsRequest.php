<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustUserCreditsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'between:-1000000,1000000', 'not_in:0'],
            'idempotency_key' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
