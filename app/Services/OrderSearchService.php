<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OrderSearchService
{
    /**
     * Tìm kiếm đơn hàng với performance tối ưu
     */
    public function search($filters = [], $perPage = 20)
    {
        $query = Order::query();
        
        // Tối ưu select chỉ những fields cần thiết cho grid
        $query->select([
            'id', 'order_id', 'system_id', 'pos_global_id', 'bill_phone_number', 'bill_full_name', 
            'status', 'status_name', 'page_name', 'cod', 'total_quantity', 
            'inserted_at', 'created_at'
        ]);

        // Apply filters với indexes tối ưu
        $this->applyFilters($query, $filters);
        
        // Default ordering sử dụng composite index
        $query->orderBy('inserted_at', 'desc')->orderBy('id', 'desc');
        
        return $query->paginate($perPage);
    }

    /**
     * Quick search cho autocomplete
     */
    public function quickSearch($term, $limit = 10)
    {
        if (strlen($term) < 2) {
            return collect([]);
        }

        // Cache kết quả 5 phút
        $cacheKey = "order_quick_search:" . md5($term);
        
        return Cache::remember($cacheKey, 300, function () use ($term, $limit) {
            $results = collect();
            
            // Search by phone (exact match có priority cao nhất)
            if (preg_match('/^\d+/', $term)) {
                $phoneResults = Order::select('id', 'order_id', 'system_id', 'pos_global_id', 'bill_phone_number', 'bill_full_name', 'status')
                    ->where('bill_phone_number', 'like', $term . '%')
                    ->limit($limit)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'type' => 'phone',
                            'label' => $order->bill_phone_number . ' - ' . $order->bill_full_name,
                            'value' => $order->order_id,
                            'system_id' => $order->system_id,
                            'pos_global_id' => $order->pos_global_id,
                            'order' => $order
                        ];
                    });
                
                $results = $results->merge($phoneResults);
            }
            
            // Search by order_id
            if (strlen($term) >= 3) {
                $orderResults = Order::select('id', 'order_id', 'system_id', 'pos_global_id', 'bill_phone_number', 'bill_full_name', 'status')
                    ->where('order_id', 'like', '%' . $term . '%')
                    ->limit($limit - $results->count())
                    ->get()
                    ->map(function ($order) {
                        return [
                            'type' => 'order_id',
                            'label' => $order->order_id . ' - ' . $order->bill_full_name,
                            'value' => $order->order_id,
                            'system_id' => $order->system_id,
                            'pos_global_id' => $order->pos_global_id,
                            'order' => $order
                        ];
                    });
                
                $results = $results->merge($orderResults);
            }
            
            // Search by system_id
            if (is_numeric($term)) {
                $systemResults = Order::select('id', 'order_id', 'system_id', 'pos_global_id', 'bill_phone_number', 'bill_full_name', 'status')
                    ->where('system_id', $term)
                    ->limit($limit - $results->count())
                    ->get()
                    ->map(function ($order) {
                        return [
                            'type' => 'system_id',
                            'label' => 'System ID: ' . $order->system_id . ' - ' . $order->order_id,
                            'value' => $order->order_id,
                            'system_id' => $order->system_id,
                            'pos_global_id' => $order->pos_global_id,
                            'order' => $order
                        ];
                    });
                
                $results = $results->merge($systemResults);
            }
            
            return $results->take($limit);
        });
    }

    /**
     * Advanced search với nhiều điều kiện
     */
    public function advancedSearch($params)
    {
        $query = Order::query();
        
        // Tối ưu với index hints cho MySQL
        if (isset($params['phone']) && isset($params['status'])) {
            $query->from(DB::raw('orders USE INDEX (idx_phone_status)'));
        } elseif (isset($params['page_id']) && isset($params['phone'])) {
            $query->from(DB::raw('orders USE INDEX (idx_page_phone)'));
        }
        
        $this->applyAdvancedFilters($query, $params);
        
        return $query;
    }

    /**
     * Apply filters với performance optimization
     */
    private function applyFilters(Builder $query, array $filters)
    {
        // Phone search - ưu tiên exact match
        if (!empty($filters['phone'])) {
            $phone = $filters['phone'];
            
            if (preg_match('/^\d+$/', $phone)) {
                // Exact phone search - sử dụng index
                $query->where('bill_phone_number', $phone);
            } else {
                // Partial phone search - sử dụng FULLTEXT
                $query->whereRaw('MATCH(bill_phone_number) AGAINST(? IN BOOLEAN MODE)', ["+{$phone}*"]);
            }
        }
        
        // Order ID search
        if (!empty($filters['order_id'])) {
            $orderId = $filters['order_id'];
            
            if (strlen($orderId) > 8) {
                // Likely full order ID - exact match
                $query->where('order_id', $orderId);
            } else {
                // Partial order ID - FULLTEXT search
                $query->whereRaw('MATCH(order_id) AGAINST(? IN BOOLEAN MODE)', ["+{$orderId}*"]);
            }
        }
        
        // Customer name search
        if (!empty($filters['customer_name'])) {
            $name = $filters['customer_name'];
            $query->whereRaw('MATCH(bill_full_name) AGAINST(? IN BOOLEAN MODE)', ["+{$name}*"]);
        }
        
        // Status filter - sử dụng index
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        
        // System ID filter
        if (!empty($filters['system_id'])) {
            $query->where('system_id', $filters['system_id']);
        }
        
        // POS Global ID filter
        if (!empty($filters['pos_global_id'])) {
            $query->where('pos_global_id', $filters['pos_global_id']);
        }
        
        // Page filter
        if (!empty($filters['page_id'])) {
            $query->where('page_id', $filters['page_id']);
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('inserted_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('inserted_at', '<=', $filters['date_to']);
        }
        
        // COD range filter
        if (!empty($filters['cod_from'])) {
            $query->where('cod', '>=', $filters['cod_from']);
        }
        
        if (!empty($filters['cod_to'])) {
            $query->where('cod', '<=', $filters['cod_to']);
        }
    }

    /**
     * Apply advanced filters
     */
    private function applyAdvancedFilters(Builder $query, array $params)
    {
        // Combine multiple filters for complex queries
        
        // Phone + Status combination (có composite index)
        if (!empty($params['phone']) && isset($params['status'])) {
            $query->where('bill_phone_number', 'like', '%' . $params['phone'] . '%')
                  ->where('status', $params['status']);
        }
        
        // Page + Phone combination (có composite index)
        if (!empty($params['page_id']) && !empty($params['phone'])) {
            $query->where('page_id', $params['page_id'])
                  ->where('bill_phone_number', 'like', '%' . $params['phone'] . '%');
        }
        
        // Status + Date + Page combination (có composite index)
        if (isset($params['status']) && !empty($params['date_from']) && !empty($params['page_id'])) {
            $query->where('status', $params['status'])
                  ->where('inserted_at', '>=', $params['date_from'])
                  ->where('page_id', $params['page_id']);
        }
    }

    /**
     * Get search suggestions for autocomplete
     */
    public function getSearchSuggestions($term, $type = 'all')
    {
        $suggestions = [];
        
        switch ($type) {
            case 'phone':
                $suggestions = DB::table('orders')
                    ->select('bill_phone_number as value', DB::raw('COUNT(*) as count'))
                    ->where('bill_phone_number', 'like', $term . '%')
                    ->groupBy('bill_phone_number')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->pluck('value');
                break;
                
            case 'customer':
                $suggestions = DB::table('orders')
                    ->select('bill_full_name as value', DB::raw('COUNT(*) as count'))
                    ->whereRaw('MATCH(bill_full_name) AGAINST(? IN BOOLEAN MODE)', ["+{$term}*"])
                    ->groupBy('bill_full_name')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->pluck('value');
                break;
        }
        
        return $suggestions;
    }

    /**
     * Get dashboard statistics với cache
     */
    public function getDashboardStats()
    {
        return Cache::remember('order_dashboard_stats', 300, function () {
            return [
                'total_orders' => Order::count(),
                'today_orders' => Order::whereDate('created_at', today())->count(),
                'pending_orders' => Order::whereIn('status', [0, 1, 2])->count(),
                'completed_orders' => Order::where('status', 16)->count(),
                'total_revenue' => Order::where('status', 16)->sum('cod'),
            ];
        });
    }
}