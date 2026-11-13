<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class OwnedResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function trimFields(array $fields): void
    {
        $values = [];
        foreach ($fields as $field) {
            if ($this->exists($field) && is_string($this->input($field))) {
                $values[$field] = trim($this->input($field));
            }
        }
        $this->merge($values);
    }

    protected function protectedFields(): array
    {
        return [
            'id' => ['prohibited'],
            'user_id' => ['prohibited'],
            'userId' => ['prohibited'],
            'createdAt' => ['prohibited'],
            'updatedAt' => ['prohibited'],
        ];
    }
}
