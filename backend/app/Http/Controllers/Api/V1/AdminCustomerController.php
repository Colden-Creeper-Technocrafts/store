<?php

namespace App\Http\Controllers\Api\V1;

use App\Interfaces\CustomerRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search']);
        $perPage = min((int) $request->input('per_page', 20), 100);

        $paginator = $this->customers->list($filters, $perPage);

        return response()->json([
            'customers' => $paginator->items(),
            'meta' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $customer = $this->customers->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        return response()->json(['customer' => $customer]);
    }
}
