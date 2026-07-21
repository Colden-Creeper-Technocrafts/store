<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSocialLinksController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'links' => SocialLink::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'url'        => ['required', 'url', 'max:500'],
            'icon'       => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $link = SocialLink::create($data);

        return response()->json(['success' => true, 'link' => $link], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $link = SocialLink::findOrFail($id);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'url'        => ['required', 'url', 'max:500'],
            'icon'       => ['required', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $link->update($data);

        return response()->json(['success' => true, 'link' => $link]);
    }

    public function destroy(int $id): JsonResponse
    {
        SocialLink::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
