<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'payment_status', 'search', 'date_from', 'date_to', 'has_return', 'return_status']);
        $perPage = (int) $request->input('per_page', 20);
        $paginator = $this->orders->adminList($filters, $perPage);

        return response()->json([
            'orders' => $paginator->items(),
            'meta'   => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json(['order' => $order]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $data = $request->validate([
            'status' => ['required', 'in:' . implode(',', Order::STATUSES)],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->orders->updateStatus(
            $order,
            $data['status'],
            $request->user()->id,
            $data['note'] ?? null
        );

        return response()->json(['order' => $order]);
    }

    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $data = $request->validate([
            'payment_status' => ['required', 'in:' . implode(',', Order::PAYMENT_STATUSES)],
        ]);

        $order = $this->orders->updatePaymentStatus($order, $data['payment_status']);

        return response()->json(['order' => $order]);
    }

    public function updateTracking(Request $request, int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $data = $request->validate([
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'tracking_url'    => ['nullable', 'url', 'max:500'],
        ]);

        $order = $this->orders->updateTracking(
            $order,
            $data['tracking_number'] ?? null,
            $data['tracking_url'] ?? null
        );

        return response()->json(['order' => $order]);
    }

    public function updateNotes(Request $request, int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $data = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = $this->orders->updateAdminNotes($order, $data['admin_notes'] ?? null);

        return response()->json(['order' => $order]);
    }

    public function updateReturnStatus(Request $request, int $id): JsonResponse
    {
        $order = $this->orders->adminFind($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $data = $request->validate([
            'return_status' => ['required', 'in:' . implode(',', Order::RETURN_STATUSES)],
            'return_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = $this->orders->updateReturnStatus(
            $order,
            $data['return_status'],
            $data['return_reason'] ?? null
        );

        return response()->json(['order' => $order]);
    }
}
