<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ServiceCategory::withCount('services')
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_active']);

        return Inertia::render('admin/service-categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/service-categories/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:service_categories',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        ServiceCategory::create($data);

        return redirect()->route('service-categories.index')->with('success', 'Kategori berhasil dibuat.');
    }

    public function edit(ServiceCategory $serviceCategory): Response
    {
        return Inertia::render('admin/service-categories/Form', [
            'category' => $serviceCategory->only('id', 'name', 'description', 'is_active'),
        ]);
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:service_categories,name,'.$serviceCategory->id,
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $serviceCategory->update($data);

        return redirect()->route('service-categories.index')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        if ($serviceCategory->services()->exists()) {
            return back()->with('error', 'Tidak dapat menghapus kategori yang masih memiliki layanan.');
        }

        $serviceCategory->delete();

        return redirect()->route('service-categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
