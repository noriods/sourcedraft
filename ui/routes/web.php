<?php

use App\Http\Controllers\SourcedraftController as C;
use Illuminate\Support\Facades\Route;

Route::view('/login', 'login')->name('login');
Route::post('/login', [C::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [C::class, 'logout']);
    Route::get('/', [C::class, 'projects']);
    Route::get('/projects/{p}', [C::class, 'project']);
    Route::post('/projects/{p}/upload', [C::class, 'upload']);
    Route::post('/projects/{p}/ingest', [C::class, 'ingest']);
    Route::post('/projects/{p}/draft', [C::class, 'draft']);
    Route::get('/projects/{p}/runs/{run}', [C::class, 'run']);
    Route::post('/projects/{p}/runs/{run}/approve', [C::class, 'approve']);
    Route::get('/projects/{p}/runs/{run}/file/{name}', [C::class, 'file']);
    Route::get('/projects/{p}/runs/{run}/slide/{dir}/{name}', [C::class, 'slide']);
});
