<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class POSController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        $categories = Category::orderBy('name')->get();
        $tables = Table::where('status', 'available')->get();
        
        return view('pos.index', compact('products', 'categories', 'tables'));
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
        ]);
        
        DB::beginTransaction();
        
        try {
            // Calcular totais
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['unit_price'];
            }
            
            $discount = $validated['discount'] ?? 0;
            $tax = $subtotal * 0.10; // 10% de taxa (ajuste conforme necessário)
            $finalAmount = $subtotal - $discount + $tax;
            
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
            ]);
            
            // Adicionar itens
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                ]);
            }
            
            // Atualizar mesa se for dine_in
            if ($order->table_id) {
                $table = Table::find($order->table_id);
                $table->update(['status' => 'occupied']);
            }
            
            DB::commit();
            
            // Se for requisição AJAX/JSON, mantém retorno em JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido criado com sucesso!',
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                ]);
            }

            // Para requisições normais (form do Blade), redireciona de volta para o POS
            return redirect()
                ->route('pos.index')
                ->with('success', 'Pedido criado com sucesso!');
            
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar pedido: ' . $e->getMessage(),
                ], 500);
            }

            return back()
                ->with('error', 'Erro ao criar pedido: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function getProducts()
    {
        $products = Product::with('category')
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
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
}