<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertShelfRequest;
use App\Http\Resources\ShelfResource;
use App\Services\ShelfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShelfController extends Controller
{
    public function __construct(private ShelfService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return ShelfResource::collection($this->service->all($request->user()->id));
    }

    public function store(UpsertShelfRequest $request): JsonResponse
    {
        $resource = new ShelfResource($this->service->create($request->user()->id, $request->validated()));

        return $resource->response()->setStatusCode(201);
    }

    public function show(Request $request, int $shelf): ShelfResource
    {
        return new ShelfResource($this->service->get($request->user()->id, $shelf));
    }

    public function update(UpsertShelfRequest $request, int $shelf): ShelfResource
    {
        return new ShelfResource($this->service->update($request->user()->id, $shelf, $request->validated()));
    }

    public function destroy(Request $request, int $shelf): Response
    {
        $this->service->delete($request->user()->id, $shelf);

        return response()->noContent();
    }
}
