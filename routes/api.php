<?php

use App\Http\Controllers\Api\v1\BlogApiController;
use App\Http\Controllers\Api\v1\CategoryApiController;
use App\Http\Controllers\Api\v1\TagApiController;
use App\Http\Controllers\Api\v1\TeamApiController;
use App\Http\Controllers\Api\v1\UserApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'api.team', 'api.log'])
    ->group(function () {
        // User Profile
        Route::get('/user', [UserApiController::class, 'show']);

        // Team Management
        Route::get('/teams', [TeamApiController::class, 'index']);
        Route::put('/teams', [TeamApiController::class, 'update']);
        Route::get('/teams/members', [TeamApiController::class, 'members']);
        Route::post('/teams/members/invite', [TeamApiController::class, 'invite']);
        Route::get('/activity', [TeamApiController::class, 'activity']);

        // CRUD API Resources
        Route::apiResource('blogs', BlogApiController::class)->parameters(['blogs' => 'blog:slug']);
        Route::apiResource('categories', CategoryApiController::class)->parameters(['categories' => 'category:slug']);
        Route::apiResource('tags', TagApiController::class)->parameters(['tags' => 'tag:slug']);
    });
