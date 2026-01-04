<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Product;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Exibir a página principal do dashboard
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // Dados comuns para todos os usuários
        $data = [
            'user' => $user,
            'today' => $today->format('d/m/Y'),
        ];
        
        // Se for CLIENTE
        if (($user->role ?? '') === 'cliente') {
            $data = array_merge($data, $this->getClienteData($user));
            return view('dashboard.cliente', $data);
        }
        
        // Dashboard para funcionários
        $data = array_merge($data, $this->getFuncionarioData($user));
        
        return view('dashboard.index', $data);
    }
    
    /**
     * Dados específicos para clientes
     */
    private function getClienteData($user)
    {
        $today = Carbon::today();
        
        return [
            'meus_pedidos' => Order::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get(),
            
            'pedidos_ativos' => Order::where('user_id', $user->id)
                ->whereIn('status', ['pendente', 'preparando', 'pronto'])
                ->count(),
            
            'total_gasto' => Order::where('user_id', $user->id)
                ->where('status', '!=', 'cancelado')
                ->sum('total_amount'),
            
            'pedidos_hoje' => Order::where('user_id', $user->id)
                ->whereDate('created_at', $today)
                ->count(),
        ];
    }
    
    /**
     * Dados para funcionários
     */
    private function getFuncionarioData($user)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Calcular vendas com fallback para 0
        try {
            $vendas_hoje = Order::whereDate('created_at', $today)
                ->where('status', '!=', 'cancelado')
                ->sum('total_amount') ?: 0;
        } catch (\Exception $e) {
            $vendas_hoje = 0;
        }
        
        try {
            $vendas_ontem = Order::whereDate('created_at', $yesterday)
                ->where('status', '!=', 'cancelado')
                ->sum('total_amount') ?: 0;
        } catch (\Exception $e) {
            $vendas_ontem = 0;
        }
        
        try {
            $pedidos_pendentes = Order::where('status', 'pendente')->count() ?: 0;
        } catch (\Exception $e) {
            $pedidos_pendentes = 0;
        }
        
        try {
            $pedidos_preparando = Order::where('status', 'preparando')->count() ?: 0;
        } catch (\Exception $e) {
            $pedidos_preparando = 0;
        }
        
        try {
            $pedidos_prontos = Order::where('status', 'pronto')->count() ?: 0;
        } catch (\Exception $e) {
            $pedidos_prontos = 0;
        }
        
        try {
            $pedidos_recentes = Order::with(['user'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();
        } catch (\Exception $e) {
            $pedidos_recentes = collect([]);
        }
        
        return [
            'vendas_hoje' => $vendas_hoje,
            'vendas_ontem' => $vendas_ontem,
            'pedidos_pendentes' => $pedidos_pendentes,
            'pedidos_preparando' => $pedidos_preparando,
            'pedidos_prontos' => $pedidos_prontos,
            'pedidos_recentes' => $pedidos_recentes,
        ];
    }
    
    /**
     * Método para API - métricas do dashboard
     */
    public function metrics(Request $request)
    {
        $days = $request->get('days', 7);
        $startDate = Carbon::now()->subDays($days);
        
        $data = [];
        
        // Vendas dos últimos X dias
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($i);
            try {
                $sales = Order::whereDate('created_at', $date)
                    ->where('status', '!=', 'cancelado')
                    ->sum('total_amount') ?: 0;
            } catch (\Exception $e) {
                $sales = 0;
            }
            
            $data['sales'][] = [
                'date' => $date->format('d/m'),
                'amount' => $sales
            ];
        }
        
        // Pedidos por status
        try {
            $data['orders_by_status'] = [
                'pendente' => Order::where('status', 'pendente')->count() ?: 0,
                'preparando' => Order::where('status', 'preparando')->count() ?: 0,
                'pronto' => Order::where('status', 'pronto')->count() ?: 0,
                'entregue' => Order::where('status', 'entregue')->count() ?: 0,
                'cancelado' => Order::where('status', 'cancelado')->count() ?: 0,
            ];
        } catch (\Exception $e) {
            $data['orders_by_status'] = [
                'pendente' => 0,
                'preparando' => 0,
                'pronto' => 0,
                'entregue' => 0,
                'cancelado' => 0,
            ];
        }
        
        return response()->json($data);
    }
    
    /**
     * Dashboard de vendas (gráficos)
     */
    public function salesChart(Request $request)
    {
        $period = $request->get('period', 'week');
        
        $data = [];
        
        if ($period === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                try {
                    $sales = Order::whereDate('created_at', $date)
                        ->where('status', '!=', 'cancelado')
                        ->sum('total_amount') ?: 0;
                } catch (\Exception $e) {
                    $sales = 0;
                }
                
                $data['labels'][] = $date->format('d/m');
                $data['data'][] = $sales;
            }
        }
        
        return response()->json($data);
    }
}