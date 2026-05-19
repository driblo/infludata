<?php

declare(strict_types=1);

namespace App\Http\Requests\Creators;

use App\Support\Network;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCreatorRequest extends FormRequest
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
            'network' => ['required', 'string', Rule::in(Network::all())],
            'handle' => ['required', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
        ];
    }
}
