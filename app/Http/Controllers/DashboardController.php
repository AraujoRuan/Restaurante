<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Exibir a página principal do dashboard com métricas básicas.
     */
    public function index()
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        // Vendas
        $todaySales = Order::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('final_amount');

        $monthSales = Order::where('created_at', '>=', $monthStart)
            ->where('status', 'completed')
            ->sum('final_amount');

        // Pedidos
        $ordersToday = Order::whereDate('created_at', $today)->count();

        $pendingOrders = Order::whereIn('status', ['pending', 'preparing', 'ready'])
            ->count();

        // Mesas
        $activeTables = DB::table('tables')
            ->where('status', 'occupied')
            ->count();

        $totalTables = DB::table('tables')->count();

        // Pedidos recentes (últimos 5)
        $recentOrders = Order::orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('dashboard.index', [
            'user'          => Auth::user(),
            'today'         => $today->format('d/m/Y'),
            'todaySales'    => $todaySales,
            'monthSales'    => $monthSales,
            'ordersToday'   => $ordersToday,
            'pendingOrders' => $pendingOrders,
            'activeTables'  => $activeTables,
            'totalTables'   => $totalTables,
            'recentOrders'  => $recentOrders,
        ]);
    }
}
