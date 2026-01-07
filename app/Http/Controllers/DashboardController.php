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
        
        // Dados do cliente para o dashboard
        $user = Auth::user();
        $pedidos_hoje = Order::whereDate('created_at', $today)->count();
        $pedidos_ativos = Order::whereIn('status', ['pending', 'preparing', 'ready'])->count();
        $total_gasto = Order::where('status', 'completed')->sum('final_amount');
        $meus_pedidos = Order::orderByDesc('created_at')->take(10)->get();

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
            'pedidos_hoje'  => $pedidos_hoje,
            'pedidos_ativos' => $pedidos_ativos,
            'total_gasto'   => $total_gasto,
            'meus_pedidos'  => $meus_pedidos,
        ]);
    }
}
