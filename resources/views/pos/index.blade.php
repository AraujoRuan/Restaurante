@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">
                <i class="fas fa-cash-register me-2"></i>
                Ponto de Venda
            </h1>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Voltar para o dashboard
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <!-- Coluna esquerda: produtos -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-utensils me-1"></i> Produtos</span>
                    <form class="d-flex" method="GET">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm me-2" placeholder="Buscar produto...">
                        <button class="btn btn-sm btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    @if(isset($categories) && $categories->count())
                        <div class="mb-3">
                            <div class="btn-group" role="group">
                                <a href="{{ route('pos.index') }}" class="btn btn-sm btn-outline-secondary {{ request('category') ? '' : 'active' }}">Todos</a>
                                @foreach($categories as $category)
                                    <a href="{{ route('pos.index', ['category' => $category->id]) }}" class="btn btn-sm btn-outline-secondary {{ (int) request('category') === $category->id ? 'active' : '' }}">
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(isset($products) && $products->count())
                        <div class="row g-3">
                            @foreach($products as $product)
                                <div class="col-md-4">
                                    <div class="card h-100 border-0 shadow-sm product-card" data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}" data-product-price="{{ $product->price }}">
                                        <div class="card-body">
                                            <h6 class="card-title mb-1">{{ $product->name }}</h6>
                                            <small class="text-muted d-block mb-2">
                                                {{ $product->category->name ?? 'Sem categoria' }}
                                            </small>
                                            @if($product->description)
                                                <p class="small text-muted mb-2">{{ $product->description }}</p>
                                            @endif
                                            <strong>R$ {{ number_format($product->price, 2, ',', '.') }}</strong>
                                        </div>
                                        <div class="card-footer bg-transparent border-0 pt-0 pb-3 text-end">
                                            <button class="btn btn-sm btn-success btn-add-to-order" type="button">
                                                <i class="fas fa-plus me-1"></i>
                                                Adicionar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-box-open fa-3x mb-3"></i>
                            <p class="mb-0">Nenhum produto cadastrado ainda.</p>
                            <small>Cadastre produtos e categorias para começar a vender.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Coluna direita: carrinho / resumo do pedido -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="fas fa-receipt me-1"></i>
                    Meu Pedido
                </div>
                <div class="card-body">
                    <form id="pos-order-form" method="POST" action="{{ route('pos.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Tipo de pedido</label>
                            <select name="type" class="form-select form-select-sm">
                                <option value="dine_in">Mesa</option>
                                <option value="takeaway">Balcão</option>
                                <option value="delivery">Delivery</option>
                            </select>
                        </div>

                        @if(isset($tables) && $tables->count())
                            <div class="mb-3">
                                <label class="form-label">Mesa (opcional)</label>
                                <select name="table_id" class="form-select form-select-sm">
                                    <option value="">Selecione uma mesa</option>
                                    @foreach($tables as $table)
                                        <option value="{{ $table->id }}">Mesa {{ $table->number }} ({{ $table->capacity }} pessoas)</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Itens</label>
                            <div id="order-items-empty" class="text-center text-muted py-3">
                                <small>Nenhum item no pedido. Clique em "Adicionar" em um produto para começar.</small>
                            </div>
                            <div id="order-items-list" class="list-group d-none"></div>
                        </div>

                        <div class="mb-3 row g-2 align-items-center">
                            <div class="col-6">
                                <label class="form-label mb-0">Desconto</label>
                                <input type="number" step="0.01" min="0" name="discount" class="form-control form-control-sm" value="0">
                            </div>
                            <div class="col-6 text-end">
                                <div><small class="text-muted">Subtotal:</small> <span id="subtotal">R$ 0,00</span></div>
                                <div><small class="text-muted">Taxa (10%):</small> <span id="tax">R$ 0,00</span></div>
                                <div class="fw-bold">Total: <span id="total">R$ 0,00</span></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100" id="btn-submit-order" disabled>
                            <i class="fas fa-check me-1"></i>
                            Finalizar pedido
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const orderItemsList = document.getElementById('order-items-list');
        const orderItemsEmpty = document.getElementById('order-items-empty');
        const subtotalEl = document.getElementById('subtotal');
        const taxEl = document.getElementById('tax');
        const totalEl = document.getElementById('total');
        const discountInput = document.querySelector('input[name="discount"]');
        const submitBtn = document.getElementById('btn-submit-order');
        const form = document.getElementById('pos-order-form');

        let items = [];

        function formatMoney(value) {
            return 'R$ ' + value.toFixed(2).replace('.', ',');
        }

        function renderItems() {
            if (items.length === 0) {
                orderItemsEmpty.classList.remove('d-none');
                orderItemsList.classList.add('d-none');
                orderItemsList.innerHTML = '';
                submitBtn.disabled = true;
                subtotalEl.textContent = 'R$ 0,00';
                taxEl.textContent = 'R$ 0,00';
                totalEl.textContent = 'R$ 0,00';
                return;
            }

            orderItemsEmpty.classList.add('d-none');
            orderItemsList.classList.remove('d-none');
            orderItemsList.innerHTML = '';

            let subtotal = 0;

            items.forEach((item, index) => {
                const lineTotal = item.quantity * item.price;
                subtotal += lineTotal;

                const row = document.createElement('div');
                row.className = 'list-group-item d-flex justify-content-between align-items-center';
                row.innerHTML = `
                    <div>
                        <strong>${item.name}</strong><br>
                        <small class="text-muted">Qtd: 
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-qty" data-index="${index}" data-delta="-1">-</button>
                            <span class="mx-1">${item.quantity}</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-qty" data-index="${index}" data-delta="1">+</button>
                        </small>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted">${formatMoney(item.price)}</div>
                        <strong>${formatMoney(lineTotal)}</strong>
                        <button type="button" class="btn btn-sm btn-link text-danger p-0 d-block mt-1 btn-remove" data-index="${index}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="items[${index}][product_id]" value="${item.id}">
                    <input type="hidden" name="items[${index}][quantity]" value="${item.quantity}">
                    <input type="hidden" name="items[${index}][unit_price]" value="${item.price}">
                `;
                orderItemsList.appendChild(row);
            });

            const discount = parseFloat(discountInput.value || '0');
            const tax = subtotal * 0.10;
            const total = subtotal + tax - discount;

            subtotalEl.textContent = formatMoney(subtotal);
            taxEl.textContent = formatMoney(tax);
            totalEl.textContent = formatMoney(Math.max(total, 0));
            submitBtn.disabled = total <= 0;
        }

        document.querySelectorAll('.btn-add-to-order').forEach(btn => {
            btn.addEventListener('click', function () {
                const card = this.closest('.product-card');
                const id = parseInt(card.dataset.productId);
                const name = card.dataset.productName;
                const price = parseFloat(card.dataset.productPrice);

                const existing = items.find(i => i.id === id);
                if (existing) {
                    existing.quantity += 1;
                } else {
                    items.push({ id, name, price, quantity: 1 });
                }

                renderItems();
            });
        });

        orderItemsList.addEventListener('click', function (e) {
            if (e.target.closest('.btn-qty')) {
                const btn = e.target.closest('.btn-qty');
                const index = parseInt(btn.dataset.index);
                const delta = parseInt(btn.dataset.delta);
                items[index].quantity += delta;
                if (items[index].quantity <= 0) {
                    items.splice(index, 1);
                }
                renderItems();
            }

            if (e.target.closest('.btn-remove')) {
                const btn = e.target.closest('.btn-remove');
                const index = parseInt(btn.dataset.index);
                items.splice(index, 1);
                renderItems();
            }
        });

        discountInput.addEventListener('input', renderItems);
    });
</script>
@endpush
