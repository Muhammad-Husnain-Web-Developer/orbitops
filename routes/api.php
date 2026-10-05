<?php

use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (v1)
|--------------------------------------------------------------------------
| Personal access tokens from Settings → API. Each token is bound to one
| workspace; the user's role in that workspace still decides what it can see.
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'api.workspace', 'throttle:api'])->name('api.v1.')->group(function () {
    Route::get('/me', [ApiController::class, 'me'])->name('me');
    Route::get('/projects', [ApiController::class, 'projects'])->name('projects.index');
    Route::get('/projects/{project}', [ApiController::class, 'project'])->name('projects.show');
    Route::get('/clients', [ApiController::class, 'clients'])->name('clients.index');
    Route::get('/tasks', [ApiController::class, 'tasks'])->name('tasks.index');
    Route::post('/tasks', [ApiController::class, 'storeTask'])->name('tasks.store');
    Route::patch('/tasks/{task}', [ApiController::class, 'updateTask'])->name('tasks.update');
    Route::get('/time-entries', [ApiController::class, 'timeEntries'])->name('time-entries.index');
    Route::get('/invoices', [ApiController::class, 'invoices'])->name('invoices.index');
});
