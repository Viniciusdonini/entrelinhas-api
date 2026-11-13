<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpsertShelfRequest extends OwnedResourceRequest
{
    protected function prepareForValidation(): void
    {
        $this->trimFields(['name', 'description', 'location']);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('shelves', 'name')->where('user_id', $this->user()->id)
                    ->ignore($this->route('id')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'location' => ['sometimes', 'nullable', 'string', 'max:160'],
            ...$this->protectedFields(),
        ];
    }
}
