<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ManualJournalController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecurringTransactionController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified', 'entity.access'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Entity switcher
    Route::post('/entity/{entity}/switch', [EntityController::class, 'switch'])
        ->name('entity.switch');

    // Transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::post('/transactions/inter-entity', [TransactionController::class, 'storeInterEntity'])
        ->name('transactions.inter-entity.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/transactions/{transaction}/attachments/{attachment}', [AttachmentController::class, 'show'])
        ->name('transactions.attachments.show');

    // Manual journal (owner only)
    Route::get('/journals/create', [ManualJournalController::class, 'create'])->name('journals.create');
    Route::post('/journals', [ManualJournalController::class, 'store'])->name('journals.store');

    // Recurring transactions
    Route::get('/recurring', [RecurringTransactionController::class, 'index'])->name('recurring.index');
    Route::post('/recurring', [RecurringTransactionController::class, 'store'])->name('recurring.store');
    Route::delete('/recurring/{recurringTransaction}', [RecurringTransactionController::class, 'destroy'])
        ->name('recurring.destroy');

    // Clients (entity bisnis only)
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::post('/clients', [ClientController::class, 'store'])->name('clients.store');
    Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');

    // Projects (entity bisnis only)
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    // Invoices & piutang (entity bisnis only)
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/receivables', [InvoiceController::class, 'receivables'])->name('invoices.receivables');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])
        ->name('invoices.status.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');

    // Team management (hanya entity bisnis, hanya owner)
    Route::prefix('/entity/{entity}/team')->name('entity.team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::patch('/{user}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{user}', [TeamController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/settings.php';
