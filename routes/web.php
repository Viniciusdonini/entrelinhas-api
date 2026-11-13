<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name' => 'Entrelinhas API',
    'health' => '/api/health',
    'documentation' => 'rest.http',
    'resources' => ['books', 'authors', 'genres', 'shelves'],
]));
