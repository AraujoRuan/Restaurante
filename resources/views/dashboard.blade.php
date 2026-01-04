@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Estatísticas Rápidas -->
        <div class="col-12">
            <h2 class="mb-4">Dashboard</h2>
        </div>
        
        <!-- Cards de Métricas -->
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-muted mb-1">Vendas Hoje</h5>
                            <h2 class="mb-0">R$ {{ number_format($today_sales ?? 0, 2, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-alt fa-2x text-success"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-muted mb-1">Vendas do Mês</h5>
                            <h2 class="mb-0">R$ {{ number_format($month_sales ?? 0, 2, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-muted mb-1">Pedidos Pendentes</h5>
                            <h2 class="mb-0">{{ $pending_orders ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card border-left-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-box fa-2x text-danger"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-muted mb-1">Estoque Baixo</h5>
                            <h2 class="mb-0">{{ $low_stock ?? 0 }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pedidos Recentes -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pedidos Recentes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Mesa</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_orders ?? [] as $order)
                                <tr>
                                    <td>{{ $order->order_code }}</td>
                                    <td>
                                        @if($order->table)
                                        Mesa {{ $order->table->number }}
                                        @else
                                        Balcão
                                        @endif
                                    </td>
                                    <td>R$ {{ number_format($order->final_amount, 2, ',', '.') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'pending' ? 'warning' : 'info') }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td>{{ $order->created_at->format('d/m H:i') }}</td>
                                    <td>
                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">Nenhum pedido recente</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Produtos Mais Vendidos -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Produtos Mais Vendidos</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @forelse($top_products ?? [] as $product)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $product->name }}
                            <span class="badge bg-primary rounded-pill">{{ $product->total }}</span>
                        </li>
                        @empty
                        <li class="list-group-item text-center">Sem dados de vendas</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Acesso Rápido -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Acesso Rápido</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-cash-register fa-2x mb-2"></i>
                                <span>PDV</span>
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('orders.index') }}" class="btn btn-success btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-list-alt fa-2x mb-2"></i>
                                <span>Pedidos</span>
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('products.index') }}" class="btn btn-info btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-utensils fa-2x mb-2"></i>
                                <span>Produtos</span>
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('inventory.index') }}" class="btn btn-warning btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-boxes fa-2x mb-2"></i>
                                <span>Estoque</span>
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('expenses.index') }}" class="btn btn-danger btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                                <span>Despesas</span>
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('reports.sales') }}" class="btn btn-secondary btn-lg w-100 h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <span>Relatórios</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection