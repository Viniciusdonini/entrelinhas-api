<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertAuthorRequest;
use App\Http\Resources\AuthorResource;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AuthorController extends Controller
{
    public function __construct(private AuthorService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return AuthorResource::collection($this->service->all($request->user()->id));
    }

    public function store(UpsertAuthorRequest $request): JsonResponse
    {
        $resource = new AuthorResource($this->service->create($request->user()->id, $request->validated()));

        return $resource->response()->setStatusCode(201);
    }

    public function show(Request $request, int $author): AuthorResource
    {
        return new AuthorResource($this->service->get($request->user()->id, $author));
    }

    public function update(UpsertAuthorRequest $request, int $author): AuthorResource
    {
        return new AuthorResource($this->service->update($request->user()->id, $author, $request->validated()));
    }

    public function destroy(Request $request, int $author): Response
    {
        $this->service->delete($request->user()->id, $author);

        return response()->noContent();
    }
}
