<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Included inside the web/session and mobile/JWT authenticated route groups.
Route::get('config', [ChatController::class, 'configuration']);
Route::get('users', [ChatController::class, 'users']);
Route::get('conversations', [ChatController::class, 'conversations']);
Route::post('conversations', [ChatController::class, 'create'])->middleware('throttle:30,1');
Route::get('conversations/{conversation}/messages', [ChatController::class, 'messages']);
Route::post('conversations/{conversation}/messages', [ChatController::class, 'send'])->middleware('throttle:60,1');
Route::post('conversations/{conversation}/read', [ChatController::class, 'read'])->middleware('throttle:120,1');
Route::post('conversations/{conversation}/typing', [ChatController::class, 'typing'])->middleware('throttle:60,1');
Route::post('broadcasting/auth', function (\Illuminate\Http\Request $request) {
    abort_unless($request->user()?->enable, 403);
    $request->validate(['socket_id' => ['required', 'regex:/^\d+\.\d+$/'], 'channel_name' => 'required|string|max:200']);
    abort_unless($request->input('channel_name') === 'private-chat.user.'.$request->user()->id, 403);
    abort_unless(app(\App\Services\ChatRealtimeService::class)->enabled(), 503, 'Realtime chat is not configured.');

    return Broadcast::auth($request);
})->middleware('throttle:120,1');
