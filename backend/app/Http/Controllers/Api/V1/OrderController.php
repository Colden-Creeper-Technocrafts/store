<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Interfaces\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderRepositoryInterface $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orders->listForUser($request->user()->id);

        return response()->json(['success' => true, 'orders' => $orders]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orders->findForUser($id, $request->user()->id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        return response()->json(['success' => true, 'order' => $order]);
    }
}
