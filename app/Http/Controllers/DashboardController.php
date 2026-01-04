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
        
        // Se for CLIENTE, mostrar informações específicas
        if ($user->role === 'cliente') {
            $data = array_merge($data, $this->getClienteData($user));
            return view('dashboard.cliente', $data);
        }
        
        // Se for FUNCIONÁRIO (atendente, garçom, cozinha), mostrar dashboard geral
        $data = array_merge($data, $this->getFuncionarioData($user));
        
        // Se for ADMIN/GERENTE, mostrar dados completos
        if (in_array($user->role, ['admin', 'gerente'])) {
            $data = array_merge($data, $this->getAdminData($user));
            return view('dashboard.admin', $data);
        }
        
        // Dashboard padrão para funcionários
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
     * Dados para funcionários (atendente, garçom, cozinha) - SEM TABLE
     */
    private function getFuncionarioData($user)
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        // Calcular vendas
        $vendas_hoje = Order::whereDate('created_at', $today)
            ->where('status', '!=', 'cancelado')
            ->sum('total_amount') ?: 0;
        
        $vendas_ontem = Order::whereDate('created_at', $yesterday)
            ->where('status', '!=', 'cancelado')
            ->sum('total_amount') ?: 0;
        
        return [
            'vendas_hoje' => $vendas_hoje,
            'vendas_ontem' => $vendas_ontem,
            'pedidos_pendentes' => Order::where('status', 'pendente')->count() ?: 0,
            'pedidos_preparando' => Order::where('status', 'preparando')->count() ?: 0,
            'pedidos_prontos' => Order::where('status', 'pronto')->count() ?: 0,
            // Removido dados de mesas
            'pedidos_recentes' => Order::with(['user'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get(),
        ];
    }
    
    /**
     * Dados completos para administradores
     */
    private function getAdminData($user)
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        
        // Vendas do mês
        $vendas_mes = Order::where('created_at', '>=', $monthStart)
            ->where('status', '!=', 'cancelado')
            ->sum('total_amount') ?: 0;
        
        // Despesas do mês
        $despesas_mes = Expense::where('date', '>=', $monthStart)
            ->sum('amount') ?: 0;
        
        return [
            'vendas_mes' => $vendas_mes,
            'despesas_mes' => $despesas_mes,
            'lucro_mes' => $vendas_mes - $despesas_mes,
            'total_produtos' => Product::where('status', 'ativo')->count() ?: 0,
            'produtos_baixo_estoque' => Product::where('stock', '<', 10)
                ->where('status', 'ativo')
                ->count() ?: 0,
            'usuarios_ativos' => User::where('status', 'ativo')->count() ?: 0,
            'novos_clientes_mes' => User::where('created_at', '>=', $monthStart)
                ->count() ?: 0,
            'pedidos_mes' => Order::where('created_at', '>=', $monthStart)->count() ?: 0,
            'ticket_medio' => Order::where('created_at', '>=', $monthStart)
                ->where('status', '!=', 'cancelado')
                ->avg('total_amount') ?: 0,
        ];
    }
    
    // ... outros métodos (metrics, recentOrders, etc) ...
}