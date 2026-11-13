<?php

namespace App\Http\Requests;

use App\Enums\BookStatus;
use App\Rules\HttpsCoverUrl;
use Illuminate\Validation\Rule;

class UpsertBookRequest extends OwnedResourceRequest
{
    protected function prepareForValidation(): void
    {
        $this->trimFields(['title', 'author', 'genre', 'notes', 'coverUrl']);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'author' => ['required', 'string', 'max:100'],
            'genre' => ['sometimes', 'nullable', 'string', 'max:60'],
            'totalPages' => ['required', 'integer', 'between:1,20000'],
            'currentPage' => ['required', 'integer', 'min:0', 'lte:totalPages'],
            'status' => ['required', Rule::enum(BookStatus::class)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'coverUrl' => ['sometimes', 'nullable', 'string', 'max:2048', new HttpsCoverUrl],
            'shelfId' => [
                'sometimes', 'nullable', 'integer',
                Rule::exists('shelves', 'id')->where('user_id', $this->user()->id),
            ],
            ...$this->protectedFields(),
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título do livro.',
            'author.required' => 'Informe o nome de quem escreveu.',
            'currentPage.lte' => 'A página atual não pode ultrapassar o total de páginas.',
            'shelfId.exists' => 'Escolha uma estante da sua biblioteca.',
        ];
    }
}
