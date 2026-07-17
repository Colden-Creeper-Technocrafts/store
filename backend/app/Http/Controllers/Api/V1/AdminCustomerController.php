<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\CreateCustomerRequest;
use App\Interfaces\CustomerRepositoryInterface;
use App\Mail\WelcomeCustomerMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $plain = Str::password(12);

        $customer = $this->customers->create([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'phone'    => $request->input('phone'),
            'password' => bcrypt($plain),
        ]);

        Mail::to($customer->email)->send(new WelcomeCustomerMail(
            userName:      $customer->name,
            userEmail:     $customer->email,
            plainPassword: $plain,
        ));

        return response()->json(['success' => true, 'customer' => $customer], 201);
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
