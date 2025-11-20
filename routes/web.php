<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    try {
        return view('welcome');
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
