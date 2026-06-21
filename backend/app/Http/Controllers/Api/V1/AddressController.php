<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Interfaces\UserAddressRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct(private readonly UserAddressRepositoryInterface $addresses) {}

    public function index(Request $request): JsonResponse
    {
        $addresses = $this->addresses->listForUser($request->user()->id);

        return response()->json(['success' => true, 'addresses' => $addresses]);
    }

    public function setDefault(Request $request, int $id): JsonResponse
    {
        $address = $this->addresses->setDefault($request->user()->id, $id);

        return response()->json(['success' => true, 'address' => $address]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->addresses->delete($request->user()->id, $id);

        return response()->json(['success' => true]);
    }
}
