@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0">
                        <i class="fas fa-box-open me-2"></i>
                        Produtos
                    </h5>
                    @if(Auth::check() && Auth::user()->role === 'admin')
                        <a href="{{ route('products.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            Novo produto
                        </a>
                    @endif
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" aria-label="Close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <form method="GET" class="d-flex" role="search">
                                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Buscar por nome ou categoria...">
                                <button class="btn btn-outline-secondary ms-2" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-end">Custo</th>
                                    <th class="text-center">Ativo</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td>
                                            <strong>{{ $product->name }}</strong><br>
                                            @if($product->description)
                                                <small class="text-muted">{{ \Illuminate\Support\Str::limit($product->description, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>{{ optional($product->category)->name ?? '-' }}</td>
                                        <td class="text-end">R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                                        <td class="text-end">{{ $product->cost !== null ? 'R$ ' . number_format($product->cost, 2, ',', '.') : '-' }}</td>
                                        <td class="text-center">
                                            @if($product->active)
                                                <span class="badge bg-success">Ativo</span>
                                            @else
                                                <span class="badge bg-secondary">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            Nenhum produto cadastrado.
                                            <a href="{{ route('products.create') }}">Cadastre o primeiro produto</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $products->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
