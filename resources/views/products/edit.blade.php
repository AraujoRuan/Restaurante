@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8 offset-lg-2">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0">
                        <i class="fas fa-edit me-2"></i>
                        Editar produto
                    </h5>
                    <a href="{{ route('products.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Voltar
                    </a>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('products.update', $product) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Nome do produto *</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Categoria *</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="price" class="form-label">Preço (R$) *</label>
                                <input type="number" name="price" id="price" step="0.01" min="0" class="form-control" value="{{ old('price', $product->price) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="cost" class="form-label">Custo (R$)</label>
                                <input type="number" name="cost" id="cost" step="0.01" min="0" class="form-control" value="{{ old('cost', $product->cost) }}">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea name="description" id="description" rows="3" class="form-control">{{ old('description', $product->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">URL da imagem (opcional)</label>
                            <input type="text" name="image" id="image" class="form-control" value="{{ old('image', $product->image) }}" placeholder="https://exemplo.com/imagem.jpg">
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="active" name="active" {{ old('active', $product->active) ? 'checked' : '' }}>
                            <label class="form-label" for="active">
                                Produto ativo (disponível para venda no PDV)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-1"></i>
                            Atualizar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
