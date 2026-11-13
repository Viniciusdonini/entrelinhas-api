<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpsertGenreRequest extends OwnedResourceRequest
{
    protected function prepareForValidation(): void
    {
        $this->trimFields(['name', 'description']);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('genres', 'name')->where('user_id', $this->user()->id)
                    ->ignore($this->route('id')),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            ...$this->protectedFields(),
        ];
    }
}
