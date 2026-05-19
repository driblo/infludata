<?php

declare(strict_types=1);

namespace App\Http\Requests\Alerts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(['creator', 'own'])],
            'target_id' => ['required', 'integer'],
            'kind' => ['required', Rule::in(['follower_milestone', 'engagement_drop', 'new_content'])],
            'threshold' => ['required', 'array'],
            'channel' => ['nullable', Rule::in(['email', 'push'])],
            'enabled' => ['nullable', 'boolean'],
        ];
    }
}
