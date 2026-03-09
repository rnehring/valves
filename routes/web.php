<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoadingController;
use App\Http\Controllers\UnloadingController;
use App\Http\Controllers\ShellTestingController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MetadataController;
use App\Http\Controllers\CompanySelectController;
use App\Http\Controllers\SerialNumberController;

// Auth routes (public)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Company selection (requires login)
Route::middleware('auth.valves')->group(function () {
    Route::get('/select-company', [CompanySelectController::class, 'show'])->name('company.select');
    Route::post('/select-company', [CompanySelectController::class, 'store'])->name('company.select.post');
});

// All main routes require auth + company selected
Route::middleware(['auth.valves', 'auth.company'])->group(function () {

    // Dashboard / home
    Route::get('/', fn() => redirect()->route('loading.index'));

    // Loading
    Route::prefix('loading')->name('loading.')->middleware('can.permission:permission_loading')->group(function () {
        Route::get('/', [LoadingController::class, 'index'])->name('index');
        Route::get('/create', [LoadingController::class, 'create'])->name('create');
        Route::post('/store', [LoadingController::class, 'store'])->name('store');
        Route::get('/edit/{serialNumber}', [LoadingController::class, 'edit'])->name('edit');
    });

    // Unloading
    Route::prefix('unloading')->name('unloading.')->middleware('can.permission:permission_unloading')->group(function () {
        Route::get('/', [UnloadingController::class, 'index'])->name('index');
        Route::get('/edit/{serialNumber}', [UnloadingController::class, 'edit'])->name('edit');
        Route::post('/save', [UnloadingController::class, 'save'])->name('save');
    });

    // Shell Testing
    Route::prefix('shell-testing')->name('shell-testing.')->middleware('can.permission:permission_shellTesting')->group(function () {
        Route::get('/', [ShellTestingController::class, 'index'])->name('index');
        Route::get('/edit/{serialNumber}', [ShellTestingController::class, 'edit'])->name('edit');
        Route::post('/save', [ShellTestingController::class, 'save'])->name('save');
    });

    // Lookup
    Route::prefix('lookup')->name('lookup.')->middleware('can.permission:permission_lookup')->group(function () {
        Route::get('/', [LookupController::class, 'index'])->name('index');
        Route::get('/view/{serialNumber}', [LookupController::class, 'view'])->name('show');
        Route::get('/edit/{serialNumber}', [LookupController::class, 'edit'])->name('edit');
        Route::post('/save', [LookupController::class, 'save'])->name('save');
        Route::get('/export', [LookupController::class, 'export'])->name('export');
    });

    // Serial Numbers / Label Printing
    Route::prefix('serial-numbers')->name('serial-numbers.')->group(function () {
        Route::get('/', [SerialNumberController::class, 'index'])->name('index');
        Route::post('/lookup', [SerialNumberController::class, 'lookupJob'])->name('lookup');
        Route::post('/print', [SerialNumberController::class, 'print'])->name('print');
    });

    // Admin-only routes
    Route::middleware('admin.only')->group(function () {
        // Users
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UsersController::class, 'index'])->name('index');
            Route::get('/edit/{id?}', [UsersController::class, 'edit'])->name('edit');
            Route::post('/save', [UsersController::class, 'save'])->name('save');
            Route::get('/edit-additional/{id?}', [UsersController::class, 'editAdditional'])->name('edit-additional');
            Route::post('/save-additional', [UsersController::class, 'saveAdditional'])->name('save-additional');
            Route::get('/edit-virtual/{id?}', [UsersController::class, 'editVirtual'])->name('edit-virtual');
            Route::post('/save-virtual', [UsersController::class, 'saveVirtual'])->name('save-virtual');
        });

        // Metadata
        Route::prefix('metadata')->name('metadata.')->group(function () {
            Route::get('/', [MetadataController::class, 'index'])->name('index');
            Route::get('/edit/{id?}', [MetadataController::class, 'edit'])->name('edit');
            Route::post('/save', [MetadataController::class, 'save'])->name('save');
            Route::delete('/delete/{id}', [MetadataController::class, 'delete'])->name('delete');
        });
    });

    // Change password
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/change-password', [AuthController::class, 'changePassword'])->name('password.change.post');
});
