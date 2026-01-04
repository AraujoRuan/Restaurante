<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POSController extends Controller
{
    public function index()
    {
        // Carregar produtos com suas categorias
        $products = Product::with('category')
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        // Carregar categorias
        $categories = Category::orderBy('name')->get();
        
        // Carregar mesas disponíveis
        $tables = Table::where('status', 'available')->get();
        
        // Debug: Verificar se há produtos
        if ($products->isEmpty()) {
            // Se não houver produtos, adicionar alguns de exemplo
            $this->createSampleProducts();
            $products = Product::with('category')->where('active', true)->get();
        }
        
        return view('pos.index', compact('products', 'categories', 'tables'));
    }
    
    private function createSampleProducts()
    {
        // Verificar se já existe uma categoria
        $category = Category::first();
        
        if (!$category) {
            $category = Category::create([
                'name' => 'Lanches',
                'type' => 'food'
            ]);
        }
        
        // Criar alguns produtos de exemplo
        $sampleProducts = [
            [
                'name' => 'X-Burger',
                'description' => 'Hambúrguer com queijo',
                'price' => 25.90,
                'cost' => 10.00,
                'category_id' => $category->id,
                'active' => true,
            ],
            [
                'name' => 'Batata Frita',
                'description' => 'Porção de batata frita',
                'price' => 15.90,
                'cost' => 5.00,
                'category_id' => $category->id,
                'active' => true,
            ],
            [
                'name' => 'Refrigerante',
                'description' => 'Lata 350ml',
                'price' => 8.90,
                'cost' => 3.00,
                'category_id' => $category->id,
                'active' => true,
            ],
        ];
        
        foreach ($sampleProducts as $product) {
            Product::firstOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
    
    public function storeOrder(Request $request)
    {
        $validated = $request->validate([
            'table_id' => 'nullable|exists:tables,id',
            'type' => 'required|in:dine_in,takeaway,delivery',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Calcular totais
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            
            $discount = $validated['discount'] ?? 0;
            $tax = $subtotal * 0.10; // 10% de taxa
            $finalAmount = $subtotal + $tax - $discount;
            
            // Criar pedido
            $order = Order::create([
                'order_code' => 'ORD' . time() . rand(100, 999),
                'table_id' => $validated['table_id'] ?? null,
                'user_id' => auth()->id(),
                'type' => $validated['type'],
                'status' => 'pending',
                'total_amount' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'final_amount' => $finalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            // Adicionar itens e atualizar estoque
            foreach ($validated['items'] as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $itemTotal,
                ]);
                
                // Atualizar estoque dos ingredientes
                $this->updateInventory($item['product_id'], $item['quantity']);
            }
            
            // Atualizar status da mesa se for dine_in
            if ($order->table_id && $order->type === 'dine_in') {
                Table::where('id', $order->table_id)->update(['status' => 'occupied']);
            }
            
            DB::commit();
            
            Log::info('Pedido criado', [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'user_id' => auth()->id(),
                'total' => $finalAmount,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido criado com sucesso!',
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'total' => number_format($finalAmount, 2, ',', '.'),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erro ao criar pedido', [
                'error' => $e->getMessage(),
                'data' => $validated,
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
    
    private function updateInventory($productId, $quantity)
    {
        $product = Product::with('ingredients')->find($productId);
        
        if (!$product || !$product->ingredients) {
            return;
        }
        
        foreach ($product->ingredients as $ingredient) {
            $quantityNeeded = $ingredient->pivot->quantity * $quantity;
            
            // Verificar se há estoque suficiente
            if ($ingredient->current_stock < $quantityNeeded) {
                throw new \Exception("Estoque insuficiente para {$ingredient->name}. Disponível: {$ingredient->current_stock}, Necessário: {$quantityNeeded}");
            }
            
            // Atualizar estoque
            $ingredient->current_stock -= $quantityNeeded;
            $ingredient->save();
            
            // Registrar movimento de estoque
            StockMovement::create([
                'ingredient_id' => $ingredient->id,
                'type' => 'exit',
                'quantity' => $quantityNeeded,
                'reason' => 'Venda do produto: ' . $product->name,
                'user_id' => auth()->id(),
            ]);
        }
    }
    
    public function getProducts()
    {
        $products = Product::with('category')
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float) $product->price,
                    'category_id' => $product->category_id,
                    'category_name' => $product->category->name,
                    'description' => $product->description,
                    'image' => $product->image,
                ];
            });
            
        return response()->json($products);
    }
    
    public function filterProducts(Request $request)
    {
        $query = Product::with('category')->where('active', true);
        
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $products = $query->orderBy('name')->get();
        
        return response()->json($products);
    }
    
    public function getActiveOrders()
    {
        $orders = Order::with(['table', 'items.product'])
            ->whereIn('status', ['pending', 'preparing', 'ready'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_code' => $order->order_code,
                    'table' => $order->table ? 'Mesa ' . $order->table->number : 'Balcão',
                    'type' => $this->getOrderTypeLabel($order->type),
                    'status' => $this->getOrderStatusLabel($order->status),
                    'total' => 'R$ ' . number_format($order->final_amount, 2, ',', '.'),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'product' => $item->product->name,
                            'quantity' => $item->quantity,
                        ];
                    }),
                    'created_at' => $order->created_at->format('H:i'),
                ];
            });
            
        return response()->json($orders);
    }
    
    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,preparing,ready,served,completed,cancelled',
        ]);
        
        $order = Order::findOrFail($id);
        
        // Se for completar pedido de mesa, liberar a mesa
        if ($validated['status'] === 'completed' && $order->table_id && $order->type === 'dine_in') {
            Table::where('id', $order->table_id)->update(['status' => 'available']);
        }
        
        // Se for cancelar, reverter estoque
        if ($validated['status'] === 'cancelled') {
            $this->revertInventory($order);
        }
        
        $order->update(['status' => $validated['status']]);
        
        return response()->json([
            'success' => true,
            'message' => 'Status do pedido atualizado!',
        ]);
    }
    
    private function revertInventory($order)
    {
        foreach ($order->items as $item) {
            $product = Product::with('ingredients')->find($item->product_id);
            
            if ($product && $product->ingredients) {
                foreach ($product->ingredients as $ingredient) {
                    $quantityReverted = $ingredient->pivot->quantity * $item->quantity;
                    
                    // Retornar ao estoque
                    $ingredient->current_stock += $quantityReverted;
                    $ingredient->save();
                    
                    // Registrar movimento de estoque
                    StockMovement::create([
                        'ingredient_id' => $ingredient->id,
                        'type' => 'entry',
                        'quantity' => $quantityReverted,
                        'reason' => 'Cancelamento do pedido: ' . $order->order_code,
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }
    }
    
    private function getOrderTypeLabel($type)
    {
        $labels = [
            'dine_in' => 'Mesa',
            'takeaway' => 'Balcão',
            'delivery' => 'Delivery',
        ];
        
        return $labels[$type] ?? $type;
    }
    
    private function getOrderStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pendente',
            'preparing' => 'Preparando',
            'ready' => 'Pronto',
            'served' => 'Servido',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
        ];
        
        return $labels[$status] ?? $status;
    }
    
    public function getTablesStatus()
    {
        $tables = Table::orderBy('number')->get()->map(function ($table) {
            return [
                'id' => $table->id,
                'number' => $table->number,
                'capacity' => $table->capacity,
                'status' => $table->status,
                'status_label' => $this->getTableStatusLabel($table->status),
                'status_class' => $this->getTableStatusClass($table->status),
            ];
        });
        
        return response()->json($tables);
    }
    
    private function getTableStatusLabel($status)
    {
        $labels = [
            'available' => 'Disponível',
            'occupied' => 'Ocupada',
            'reserved' => 'Reservada',
            'maintenance' => 'Manutenção',
        ];
        
        return $labels[$status] ?? $status;
    }
    
    private function getTableStatusClass($status)
    {
        $classes = [
            'available' => 'success',
            'occupied' => 'danger',
            'reserved' => 'warning',
            'maintenance' => 'secondary',
        ];
        
        return $classes[$status] ?? 'secondary';
    }
    
    public function getDashboardData()
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        
        $data = [
            'today_sales' => Order::where('created_at', '>=', $today)
                ->where('status', 'completed')
                ->sum('final_amount'),
                
            'today_orders' => Order::where('created_at', '>=', $today)->count(),
            
            'pending_orders' => Order::where('status', 'pending')->count(),
            
            'active_tables' => Table::where('status', 'occupied')->count(),
            
            'total_tables' => Table::count(),
            
            'recent_orders' => Order::with('table')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($order) {
                    return [
                        'code' => $order->order_code,
                        'table' => $order->table ? 'Mesa ' . $order->table->number : 'Balcão',
                        'total' => 'R$ ' . number_format($order->final_amount, 2, ',', '.'),
                        'status' => $order->status,
                        'time' => $order->created_at->diffForHumans(),
                    ];
                }),
        ];
        
        return response()->json($data);
    }
}