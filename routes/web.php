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

Route::get('/redis-test', function () {
    try {
        $redis = Illuminate\Support\Facades\Redis::connection();

        // Test basic operations
        $redis->set('test_key', 'Hello from Valkey!');
        $value = $redis->get('test_key');

        // Get Redis info
        $info = $redis->info();

        return response()->json([
            'status' => 'success',
            'message' => 'Redis/Valkey connection successful',
            'test_value' => $value,
            'server_version' => $info['Server']['redis_version'] ?? 'Unknown',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Redis/Valkey connection failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});
