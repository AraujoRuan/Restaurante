@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Bem-vindo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">Olá, {{ Auth::user()->name }}!</h1>
                            <p class="mb-0">Bem-vindo(a) ao Sistema Restaurante</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-user-circle fa-5x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cards de informações -->
    <div class="row mb-4">
        <!-- Pedidos Hoje -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Pedidos Hoje
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pedidos_hoje }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pedidos Ativos -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pedidos Ativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $pedidos_ativos }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Total Gasto -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Gasto
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($total_gasto, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Meu Perfil -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Meu Status
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                Cliente
                            </div>
                            <small class="text-muted">Desde {{ Auth::user()->created_at->format('d/m/Y') }}</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Meus Pedidos Recentes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Meus Pedidos Recentes</h6>
                    <a href="#" class="btn btn-sm btn-primary">
                        <i class="fas fa-history me-1"></i> Histórico Completo
                    </a>
                </div>
                <div class="card-body">
                    @if($meus_pedidos && $meus_pedidos->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($meus_pedidos as $pedido)
                                <tr>
                                    <td>#{{ $pedido->id }}</td>
                                    <td>{{ $pedido->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $pedido->status === 'pendente' ? 'warning' : 
                                                                 ($pedido->status === 'preparando' ? 'info' : 
                                                                 ($pedido->status === 'pronto' ? 'success' : 
                                                                 ($pedido->status === 'entregue' ? 'primary' : 'secondary'))) }}">
                                            {{ ucfirst($pedido->status) }}
                                        </span>
                                    </td>
                                    <td>R$ {{ number_format($pedido->total_amount, 2, ',', '.') }}</td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($pedido->status === 'pendente')
                                        <a href="#" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-basket fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum pedido realizado</h5>
                        <p class="text-muted">Faça seu primeiro pedido!</p>
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-utensils me-1"></i> Ver Cardápio
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Ações para Cliente -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">O que você gostaria de fazer?</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-primary w-100 h-100 py-4">
                                <i class="fas fa-utensils fa-2x mb-2"></i><br>
                                Ver Cardápio
                            </a>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-success w-100 h-100 py-4">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                Novo Pedido
                            </a>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="{{ route('profile') }}" class="btn btn-info w-100 h-100 py-4">
                                <i class="fas fa-user-edit fa-2x mb-2"></i><br>
                                Meu Perfil
                            </a>
                        </div>
                        
                        <div class="col-md-3 col-sm-6 mb-3">
                            <a href="#" class="btn btn-warning w-100 h-100 py-4">
                                <i class="fas fa-question-circle fa-2x mb-2"></i><br>
                                Ajuda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection