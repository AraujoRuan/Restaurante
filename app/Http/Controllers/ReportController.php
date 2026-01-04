<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Expense;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Carbon\CarbonPeriod;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());
        
        $sales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as date, SUM(final_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
            
        $total = $sales->sum('total');
        
        return view('reports.sales', compact('sales', 'total', 'startDate', 'endDate'));
    }
    
    public function profit(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());
        
        // Receitas
        $revenue = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('final_amount');
            
        // Despesas
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->sum('amount');
            
        // Custo dos produtos vendidos (simplificado)
        $cogs = $this->calculateCOGS($startDate, $endDate);
        
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;
        
        $profitMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;
        
        return view('reports.profit', compact(
            'revenue', 'expenses', 'cogs', 'grossProfit', 
            'netProfit', 'profitMargin', 'startDate', 'endDate'
        ));
    }
    
    private function calculateCOGS($startDate, $endDate)
    {
        // Calcular custo dos ingredientes utilizados nos pedidos
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('items.product.ingredients')
            ->get();
            
        $totalCost = 0;
        
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                foreach ($item->product->ingredients as $ingredient) {
                    $quantityUsed = $ingredient->pivot->quantity * $item->quantity;
                    $cost = $quantityUsed * $ingredient->last_cost;
                    $totalCost += $cost;
                }
            }
        }
        
        return $totalCost;
    }
    
    public function generate(Request $request)
    {
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        switch ($type) {
            case 'sales':
                $data = $this->getSalesData($startDate, $endDate);
                $pdf = PDF::loadView('reports.pdf.sales', $data);
                break;
                
            case 'profit':
                $data = $this->getProfitData($startDate, $endDate);
                $pdf = PDF::loadView('reports.pdf.profit', $data);
                break;
        }
        
        return $pdf->download("relatorio_{$type}_{$startDate}_{$endDate}.pdf");
    }
}