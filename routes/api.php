<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\GenreController;
use App\Http\Controllers\Api\ShelfController;
use Illuminate\Support\Facades\Route;

Route::get('health', fn () => response()->json(['status' => 'ok', 'service' => 'Entrelinhas API']));
Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    foreach ([
        'authors' => AuthorController::class,
        'genres' => GenreController::class,
        'shelves' => ShelfController::class,
        'books' => BookController::class,
    ] as $resource => $controller) {
        Route::get($resource, [$controller, 'index'])->name($resource.'.index');
        Route::post($resource, [$controller, 'store'])->name($resource.'.store');
        Route::get($resource.'/{id}', [$controller, 'show'])->whereNumber('id')->name($resource.'.show');
        Route::put($resource.'/{id}', [$controller, 'update'])->whereNumber('id')->name($resource.'.update');
        Route::delete($resource.'/{id}', [$controller, 'destroy'])->whereNumber('id')->name($resource.'.destroy');
    }
});
