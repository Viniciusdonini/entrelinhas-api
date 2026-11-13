<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpsertAuthorRequest extends OwnedResourceRequest
{
    protected function prepareForValidation(): void
    {
        $this->trimFields(['name', 'bio']);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('authors', 'name')->where('user_id', $this->user()->id)
                    ->ignore($this->route('id')),
            ],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            ...$this->protectedFields(),
        ];
    }
}
