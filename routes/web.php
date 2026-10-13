<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
Route::middleware(SetLocale::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });
    Route::middleware('auth')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::get('/tasks/{task}/activities', [\App\Http\Controllers\TaskActivityController::class, 'index'])->name('tasks.activities.index');
        Route::post('/tasks/{task}/activities', [\App\Http\Controllers\TaskActivityController::class, 'store'])->name('tasks.activities.store');
        Route::get('/tasks/{task}/activities/{activity}/edit', [\App\Http\Controllers\TaskActivityController::class, 'edit'])->name('tasks.activities.edit');
        Route::patch('/tasks/{task}/activities/{activity}', [\App\Http\Controllers\TaskActivityController::class, 'update'])->name('tasks.activities.update');
        Route::get('/dashboard', [TaskController::class, 'dashboard'])->middleware('can:view-dashboard')->name('dashboard');
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    });
    Route::post('/language', function (Request $request) {
        $data = $request->validate(['locale' => ['required', 'string', Rule::in(['en', 'ms'])]]);
        $request->session()->put('locale', $data['locale']);
        return redirect()->route(auth()->check() ? 'tasks.index' : 'login');
    })->name('language.update');
});
