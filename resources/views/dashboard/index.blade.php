@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h1 class="h3 mb-2">Dashboard</h1>
    <p class="text-muted mb-4">
        Olá, {{ $user->name ?? 'usuário' }}. Hoje é {{ $today }}.
    </p>

    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Vendas Hoje</h6>
                    <h3>R$ {{ number_format($todaySales, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Vendas do Mês</h6>
                    <h3>R$ {{ number_format($monthSales, 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Pedidos Hoje</h6>
                    <h3>{{ $ordersToday }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-danger h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase mb-2">Pedidos Pendentes</h6>
                    <h3>{{ $pendingOrders }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Mesas</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong>Mesas ocupadas:</strong> {{ $activeTables }}
                    </p>
                    <p class="mb-0">
                        <strong>Total de mesas:</strong> {{ $totalTables }}
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Pedidos Recentes</h5>
                </div>
                <div class="card-body">
                    @if($recentOrders->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                        <tr>
                                            <td>{{ $order->order_code ?? $order->id }}</td>
                                            <td>R$ {{ number_format($order->final_amount, 2, ',', '.') }}</td>
                                            <td>{{ $order->status }}</td>
                                            <td>{{ $order->created_at->format('d/m H:i') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted mb-0">Nenhum pedido recente.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
