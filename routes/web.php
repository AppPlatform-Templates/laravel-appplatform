<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

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

Route::get('/db-test', function () {
    try {
        DB::connection()->getPdo();

        $dbName = DB::connection()->getDatabaseName();
        $result = DB::select('SELECT version()');

        return response()->json([
            'status' => 'success',
            'message' => 'Database connection successful',
            'database' => $dbName,
            'version' => $result[0]->version ?? 'Unknown',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database connection failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});
