<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CheckpointController;
use App\Http\Controllers\ProjectController;
use App\Models\Checkpoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::get('/auth/github', [AuthController::class, 'redirect'])->middleware('throttle:oauth')->name('auth.github');
    Route::get('/auth/github/callback', [AuthController::class, 'callback'])->middleware('throttle:oauth')->name('auth.callback');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'owner'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        $query = $request->user()->projects();
        $projects = (clone $query)->latest('updated_at')->limit(3)->get();
        $counts = ['Total proyek' => (clone $query)->count(), 'Aktif' => (clone $query)->whereIn('status', ['Planning', 'Development', 'Testing', 'Blocked'])->count(), 'Blocked' => (clone $query)->where('status', 'Blocked')->count(), 'Testing' => (clone $query)->where('status', 'Testing')->count()];
        $checkpoints = Checkpoint::with('project')->whereHas('project', fn ($query) => $query->where('user_id', $request->user()->id))->orderByDesc('occurred_at')->orderByDesc('id')->limit(3)->get();

        return view('dashboard', compact('projects', 'counts', 'checkpoints'));
    })->name('dashboard');
    Route::view('/projects', 'projects.index')->name('projects.index');
    Route::post('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
    Route::resource('projects', ProjectController::class)->except('index');
    Route::post('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::post('/projects/{project}/sync', [ProjectController::class, 'sync'])->middleware('throttle:github-sync')->name('projects.sync');
    Route::get('/projects/{project}/checkpoints/create', [CheckpointController::class, 'create'])->name('checkpoints.create');
    Route::post('/projects/{project}/checkpoints', [CheckpointController::class, 'store'])->name('checkpoints.store');
    Route::get('/projects/{project}/handoff', [CheckpointController::class, 'downloadCurrent'])->name('handoff.current');
    Route::get('/projects/{project}/handoff/{checkpoint}', [CheckpointController::class, 'download'])->scopeBindings()->name('handoff.download');
    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::get('/backup/export', [BackupController::class, 'export'])->middleware('throttle:10,1')->name('backup.export');
    Route::post('/backup/preview', [BackupController::class, 'preview'])->middleware('throttle:10,1')->name('backup.preview');
    Route::post('/backup/import', [BackupController::class, 'import'])->middleware('throttle:10,1')->name('backup.import');
});
