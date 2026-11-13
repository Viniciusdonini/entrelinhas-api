<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertBookRequest;
use App\Http\Resources\BookResource;
use App\Services\BookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookController extends Controller
{
    public function __construct(private BookService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return BookResource::collection($this->service->all($request->user()->id));
    }

    public function store(UpsertBookRequest $request): JsonResponse
    {
        $resource = new BookResource($this->service->create($request->user()->id, $request->validated()));

        return $resource->response()->setStatusCode(201);
    }

    public function show(Request $request, int $book): BookResource
    {
        return new BookResource($this->service->get($request->user()->id, $book));
    }

    public function update(UpsertBookRequest $request, int $book): BookResource
    {
        return new BookResource($this->service->update($request->user()->id, $book, $request->validated()));
    }

    public function destroy(Request $request, int $book): Response
    {
        $this->service->delete($request->user()->id, $book);

        return response()->noContent();
    }
}
