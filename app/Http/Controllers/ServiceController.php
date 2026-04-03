<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    /**
     * Ambil semua layanan aktif beserta kategorinya untuk form POS.
     */
    public function forPos(): JsonResponse
    {
        $categories = ServiceCategory::with(['services' => function ($q) {
            $q->where('is_active', true)->orderBy('name');
        }])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'services' => $cat->services->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'price' => $s->price,
                ]),
            ]);

        return response()->json($categories);
    }
}
