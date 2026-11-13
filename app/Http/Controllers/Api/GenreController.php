<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertGenreRequest;
use App\Http\Resources\GenreResource;
use App\Services\GenreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class GenreController extends Controller
{
    public function __construct(private GenreService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return GenreResource::collection($this->service->all($request->user()->id));
    }

    public function store(UpsertGenreRequest $request): JsonResponse
    {
        $resource = new GenreResource($this->service->create($request->user()->id, $request->validated()));

        return $resource->response()->setStatusCode(201);
    }

    public function show(Request $request, int $genre): GenreResource
    {
        return new GenreResource($this->service->get($request->user()->id, $genre));
    }

    public function update(UpsertGenreRequest $request, int $genre): GenreResource
    {
        return new GenreResource($this->service->update($request->user()->id, $genre, $request->validated()));
    }

    public function destroy(Request $request, int $genre): Response
    {
        $this->service->delete($request->user()->id, $genre);

        return response()->noContent();
    }
}
