<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'author' => $this->author->name,
            'authorId' => (string) $this->author_id,
            'genre' => $this->genre?->name ?? '',
            'genreId' => $this->genre_id === null ? null : (string) $this->genre_id,
            'shelf' => $this->shelf?->name ?? '',
            'shelfId' => $this->shelf_id === null ? null : (string) $this->shelf_id,
            'totalPages' => $this->total_pages,
            'currentPage' => $this->current_page,
            'status' => $this->status,
            'notes' => $this->notes,
            'coverUrl' => $this->cover_url,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
