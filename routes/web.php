<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    RegisterController,
    DashboardController,
    UserController,
    ProductController,
    CategoryController,
    OrderController,
    ExpenseController,
    InventoryController,
    ReportController,
    POSController,
    TableController,
    IngredientController,
    PaymentController
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
    
    // Registro
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Termos e Privacidade
    Route::get('/terms', [RegisterController::class, 'showTerms'])->name('terms');
    Route::get('/privacy', [RegisterController::class, 'showPrivacy'])->name('privacy');
    
    // Recuperação de senha (opcional)
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Rotas Públicas Especiais (API para PDV, se necessário)
|--------------------------------------------------------------------------
*/

// API pública para consulta de produtos (se quiser expor para delivery externo)
Route::get('/api/products', [ProductController::class, 'apiIndex'])->name('api.products');

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
    |--------------------------------------------------------------------------
    */
    Route::prefix('pos')->group(function () {
        Route::get('/', [POSController::class, 'index'])->name('pos.index');
        Route::post('/store', [POSController::class, 'storeOrder'])->name('pos.store');
        Route::get('/products', [POSController::class, 'getProducts'])->name('pos.products');
        Route::post('/filter-products', [POSController::class, 'filterProducts'])->name('pos.filter-products');
        Route::get('/active-orders', [POSController::class, 'getActiveOrders'])->name('pos.active-orders');
        Route::get('/tables-status', [POSController::class, 'getTablesStatus'])->name('pos.tables-status');
        Route::get('/dashboard-data', [POSController::class, 'getDashboardData'])->name('pos.dashboard-data');
        Route::post('/order/{id}/status', [POSController::class, 'updateOrderStatus'])->name('pos.update-status');
        Route::get('/kitchen', [POSController::class, 'kitchenView'])->name('pos.kitchen');
        Route::get('/cashier', [POSController::class, 'cashierView'])->name('pos.cashier');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Produtos
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::get('/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::get('/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::get('/export', [ProductController::class, 'export'])->name('products.export');
        Route::post('/import', [ProductController::class, 'import'])->name('products.import');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Categorias de Produtos
    |--------------------------------------------------------------------------
    */
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('categories.show');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Pedidos
    |--------------------------------------------------------------------------
    */
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/create', [OrderController::class, 'create'])->name('orders.create');
        Route::post('/', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('orders.update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');
        Route::post('/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('/{order}/payment', [OrderController::class, 'addPayment'])->name('orders.add-payment');
        Route::get('/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
        Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/today', [OrderController::class, 'today'])->name('orders.today');
        Route::get('/pending', [OrderController::class, 'pending'])->name('orders.pending');
        Route::get('/completed', [OrderController::class, 'completed'])->name('orders.completed');
        Route::post('/bulk-status', [OrderController::class, 'bulkUpdateStatus'])->name('orders.bulk-status');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Despesas
    |--------------------------------------------------------------------------
    */
    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');
        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::post('/import', [ExpenseController::class, 'import'])->name('expenses.import');
        Route::get('/export', [ExpenseController::class, 'export'])->name('expenses.export');
        Route::get('/categories', [ExpenseController::class, 'categories'])->name('expenses.categories');
        Route::get('/monthly', [ExpenseController::class, 'monthly'])->name('expenses.monthly');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Estoque/Inventário
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/create', [InventoryController::class, 'create'])->name('inventory.create');
        Route::post('/', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('/{ingredient}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::get('/{ingredient}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
        Route::put('/{ingredient}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/{ingredient}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::post('/{ingredient}/movement', [InventoryController::class, 'addMovement'])->name('inventory.movement');
        Route::get('/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::post('/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust');
        Route::post('/waste', [InventoryController::class, 'registerWaste'])->name('inventory.waste');
        Route::get('/reorder', [InventoryController::class, 'reorderList'])->name('inventory.reorder');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Ingredientes
    |--------------------------------------------------------------------------
    */
    Route::prefix('ingredients')->group(function () {
        Route::get('/', [IngredientController::class, 'index'])->name('ingredients.index');
        Route::get('/create', [IngredientController::class, 'create'])->name('ingredients.create');
        Route::post('/', [IngredientController::class, 'store'])->name('ingredients.store');
        Route::get('/{ingredient}', [IngredientController::class, 'show'])->name('ingredients.show');
        Route::get('/{ingredient}/edit', [IngredientController::class, 'edit'])->name('ingredients.edit');
        Route::put('/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
        Route::delete('/{ingredient}', [IngredientController::class, 'destroy'])->name('ingredients.destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Gestão de Mesas
    |--------------------------------------------------------------------------
    */
    Route::prefix('tables')->group(function () {
        Route::get('/', [TableController::class, 'index'])->name('tables.index');
        Route::get('/create', [TableController::class, 'create'])->name('tables.create');
        Route::post('/', [TableController::class, 'store'])->name('tables.store');
        Route::get('/{table}', [TableController::class, 'show'])->name('tables.show');
        Route::get('/{table}/edit', [TableController::class, 'edit'])->name('tables.edit');
        Route::put('/{table}', [TableController::class, 'update'])->name('tables.update');
        Route::delete('/{table}', [TableController::class, 'destroy'])->name('tables.destroy');
        Route::post('/{table}/status', [TableController::class, 'updateStatus'])->name('tables.update-status');
        Route::get('/layout', [TableController::class, 'layout'])->name('tables.layout');
        Route::post('/layout/update', [TableController::class, 'updateLayout'])->name('tables.update-layout');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Relatórios
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        // Vendas
        Route::get('/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/sales/daily', [ReportController::class, 'dailySales'])->name('reports.sales.daily');
        Route::get('/sales/monthly', [ReportController::class, 'monthlySales'])->name('reports.sales.monthly');
        Route::get('/sales/by-product', [ReportController::class, 'salesByProduct'])->name('reports.sales.by-product');
        Route::get('/sales/by-category', [ReportController::class, 'salesByCategory'])->name('reports.sales.by-category');
        
        // Despesas
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('reports.expenses');
        Route::get('/expenses/daily', [ReportController::class, 'dailyExpenses'])->name('reports.expenses.daily');
        Route::get('/expenses/monthly', [ReportController::class, 'monthlyExpenses'])->name('reports.expenses.monthly');
        Route::get('/expenses/by-category', [ReportController::class, 'expensesByCategory'])->name('reports.expenses.by-category');
        
        // Lucratividade
        Route::get('/profit', [ReportController::class, 'profit'])->name('reports.profit');
        Route::get('/profit/daily', [ReportController::class, 'dailyProfit'])->name('reports.profit.daily');
        Route::get('/profit/monthly', [ReportController::class, 'monthlyProfit'])->name('reports.profit.monthly');
        
        // Estoque
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('/inventory/movements', [ReportController::class, 'inventoryMovements'])->name('reports.inventory.movements');
        Route::get('/inventory/waste', [ReportController::class, 'inventoryWaste'])->name('reports.inventory.waste');
        
        // Pedidos
        Route::get('/orders', [ReportController::class, 'orders'])->name('reports.orders');
        Route::get('/orders/by-status', [ReportController::class, 'ordersByStatus'])->name('reports.orders.by-status');
        Route::get('/orders/by-hour', [ReportController::class, 'ordersByHour'])->name('reports.orders.by-hour');
        
        // Exportação
        Route::post('/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/export/pdf/{type}', [ReportController::class, 'exportPDF'])->name('reports.export.pdf');
        Route::get('/export/excel/{type}', [ReportController::class, 'exportExcel'])->name('reports.export.excel');
        
        // Dashboard
        Route::get('/dashboard-data', [ReportController::class, 'dashboardData'])->name('reports.dashboard-data');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Configurações do Sistema
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
    
    /*
    |--------------------------------------------------------------------------
    | API Interna (para AJAX)
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        // Dados para gráficos
        Route::get('/sales-chart', [ReportController::class, 'salesChart'])->name('api.sales-chart');
        Route::get('/expenses-chart', [ReportController::class, 'expensesChart'])->name('api.expenses-chart');
        Route::get('/profit-chart', [ReportController::class, 'profitChart'])->name('api.profit-chart');
        
        // Dados para dashboards
        Route::get('/dashboard-metrics', [DashboardController::class, 'metrics'])->name('api.dashboard-metrics');
        Route::get('/recent-orders', [DashboardController::class, 'recentOrders'])->name('api.recent-orders');
        Route::get('/top-products', [DashboardController::class, 'topProducts'])->name('api.top-products');
        
        // Notificações
        Route::get('/notifications', [DashboardController::class, 'notifications'])->name('api.notifications');
        Route::post('/notifications/{id}/read', [DashboardController::class, 'markNotificationAsRead'])->name('api.notifications.read');
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