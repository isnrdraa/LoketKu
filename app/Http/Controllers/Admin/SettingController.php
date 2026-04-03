<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('admin/settings/Index', [
            'settings' => [
                'store_name'    => Setting::get('store_name', config('app.name')),
                'store_address' => Setting::get('store_address', ''),
                'store_phone'   => Setting::get('store_phone', ''),
                'store_footer'  => Setting::get('store_footer', ''),
                'timezone'      => Setting::get('timezone', 'Asia/Jakarta'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'store_name'    => 'required|string|max:100',
            'store_address' => 'nullable|string|max:255',
            'store_phone'   => 'nullable|string|max:50',
            'store_footer'  => 'nullable|string|max:255',
            'timezone'      => 'required|string|timezone',
        ]);

        foreach ($data as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return back()->with('success', 'Pengaturan toko berhasil disimpan.');
    }
}
