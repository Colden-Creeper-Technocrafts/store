<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminAnalyticsController
{
    public function summary(): JsonResponse
    {
        $periodStart   = now()->subDays(29)->startOfDay();
        $prevStart     = now()->subDays(59)->startOfDay();
        $prevEnd       = now()->subDays(30)->endOfDay();

        // ── Revenue ───────────────────────────────────────────────────────────
        $totalRevenue  = (float) Order::where('payment_status', 'paid')->sum('total');
        $periodRevenue = (float) Order::where('payment_status', 'paid')->where('created_at', '>=', $periodStart)->sum('total');
        $prevRevenue   = (float) Order::where('payment_status', 'paid')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('total');

        // ── Orders ────────────────────────────────────────────────────────────
        $totalOrders  = Order::count();
        $periodOrders = Order::where('created_at', '>=', $periodStart)->count();
        $prevOrders   = Order::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        // ── Customers ─────────────────────────────────────────────────────────
        $totalCustomers = User::role('customer')->count();
        $newCustomers   = User::role('customer')->where('created_at', '>=', $periodStart)->count();

        // ── Average Order Value ───────────────────────────────────────────────
        $aov       = round((float) (Order::where('payment_status', 'paid')->avg('total') ?? 0), 2);
        $periodAov = round((float) (Order::where('payment_status', 'paid')->where('created_at', '>=', $periodStart)->avg('total') ?? 0), 2);

        // ── Daily revenue trend (last 30 days) ────────────────────────────────
        $dailyTrend = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $periodStart)
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Recent orders ─────────────────────────────────────────────────────
        $recentOrders = Order::with('user:id,name,email')
            ->latest()
            ->limit(8)
            ->get(['id', 'user_id', 'status', 'payment_status', 'total', 'shipping_name', 'created_at']);

        // ── Top products last 30 days ─────────────────────────────────────────
        $topProducts = OrderItem::whereHas('order', fn ($q) => $q->where('created_at', '>=', $periodStart))
            ->selectRaw('name, SUM(quantity) as units_sold, SUM(subtotal) as revenue')
            ->groupBy('name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // ── Order status breakdown ────────────────────────────────────────────
        $statusBreakdown = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // ── Inventory alerts ──────────────────────────────────────────────────
        $lowStock = Product::where('quantity', '>', 0)
            ->where('quantity', '<=', 5)
            ->orderBy('quantity')
            ->limit(5)
            ->get(['id', 'name', 'sku', 'quantity']);

        $outOfStockCount = Product::where('quantity', '<=', 0)->count();

        return response()->json([
            'kpis' => [
                'revenue' => [
                    'total'      => $totalRevenue,
                    'period'     => $periodRevenue,
                    'change_pct' => $prevRevenue > 0
                        ? round((($periodRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
                        : null,
                ],
                'orders' => [
                    'total'      => $totalOrders,
                    'period'     => $periodOrders,
                    'change_pct' => $prevOrders > 0
                        ? round((($periodOrders - $prevOrders) / $prevOrders) * 100, 1)
                        : null,
                ],
                'customers' => [
                    'total'      => $totalCustomers,
                    'new_period' => $newCustomers,
                ],
                'aov' => [
                    'total'  => $aov,
                    'period' => $periodAov,
                ],
            ],
            'revenue_trend'          => $dailyTrend,
            'recent_orders'          => $recentOrders,
            'top_products'           => $topProducts,
            'order_status_breakdown' => $statusBreakdown,
            'low_stock'              => $lowStock,
            'out_of_stock_count'     => $outOfStockCount,
        ]);
    }
}
