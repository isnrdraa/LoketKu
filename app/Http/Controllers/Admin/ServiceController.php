<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $services = Service::with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'service_category_id', 'name', 'price', 'is_active']);

        return Inertia::render('admin/services/Index', [
            'services' => $services,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/Form', [
            'categories' => ServiceCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name'                => 'required|string|max:150',
            'price'               => 'required|numeric|min:0',
            'is_active'           => 'boolean',
        ]);

        Service::create($data);

        return redirect()->route('services.index')->with('success', 'Layanan berhasil dibuat.');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/Form', [
            'service'    => $service->only('id', 'service_category_id', 'name', 'price', 'is_active'),
            'categories' => ServiceCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'service_category_id' => 'required|exists:service_categories,id',
            'name'                => 'required|string|max:150',
            'price'               => 'required|numeric|min:0',
            'is_active'           => 'boolean',
        ]);

        $service->update($data);

        return redirect()->route('services.index')->with('success', 'Layanan berhasil diperbarui.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('services.index')->with('success', 'Layanan berhasil dihapus.');
    }
}
