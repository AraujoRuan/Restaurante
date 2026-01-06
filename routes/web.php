<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    RegisterController,
    DashboardController,
    UserController,
    POSController,
    ReportController,
};

/*
|--------------------------------------------------------------------------
| Rotas Públicas (Acesso sem login)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// Grupo de rotas para visitantes (não autenticados)
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // Termos e Privacidade
    Route::get('/terms', [RegisterController::class, 'showTerms'])->name('terms');
    Route::get('/privacy', [RegisterController::class, 'showPrivacy'])->name('privacy');
    
// Recuperação de senha (comentado até os métodos existirem no AuthController)
    // Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    // Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    // Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    // Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Rotas de registro acessíveis mesmo autenticado (para permitir criar conta a partir do dashboard)
Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Rotas Públicas Especiais (API para PDV, se necessário)
|--------------------------------------------------------------------------
*/

// API pública para consulta de produtos (usa o método existente no POSController)
Route::get('/api/products', [POSController::class, 'getProducts'])->name('api.products');

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (Acesso apenas com login)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    /*
    |--------------------------------------------------------------------------
    | Perfil do Usuário
    |--------------------------------------------------------------------------
    */
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserController::class, 'profile'])->name('profile');
        Route::put('/', [UserController::class, 'updateProfile'])->name('profile.update');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Usuários
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::get('/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/bulk-actions', [UserController::class, 'bulkActions'])->name('users.bulk-actions');
    });
    
    /*
    |--------------------------------------------------------------------------
    | PDV - Ponto de Venda
    | Apenas rotas com métodos já implementados em POSController
    |--------------------------------------------------------------------------
    */
    Route::prefix('pos')->group(function () {
        Route::get('/', [POSController::class, 'index'])->name('pos.index');
        Route::post('/store', [POSController::class, 'storeOrder'])->name('pos.store');
        Route::get('/products', [POSController::class, 'getProducts'])->name('pos.products');
        Route::post('/filter-products', [POSController::class, 'filterProducts'])->name('pos.filter-products');
    });

    /*
    |--------------------------------------------------------------------------
    | Relatórios (mínimo viável baseado nos métodos existentes em ReportController)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/profit', [ReportController::class, 'profit'])->name('reports.profit');
        Route::post('/generate', [ReportController::class, 'generate'])->name('reports.generate');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema
    | (mantidas como closures simples; implementar views conforme necessário)
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->group(function () {
        Route::get('/', function () {
            return view('settings.index');
        })->name('settings.index');
        
        Route::get('/restaurant', function () {
            return view('settings.restaurant');
        })->name('settings.restaurant');
        
        Route::get('/taxes', function () {
            return view('settings.taxes');
        })->name('settings.taxes');
        
        Route::get('/printers', function () {
            return view('settings.printers');
        })->name('settings.printers');
        
        Route::get('/backup', function () {
            return view('settings.backup');
        })->name('settings.backup');
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de Fallback (404)
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});