<?php

namespace App\Http\Controllers;

use App\Actions\StoreTransactionAction;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\ServiceCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Daftar transaksi dengan filter.
     */
    public function index(Request $request): Response
    {
        $query = Transaction::with('cashier')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        // Kasir hanya lihat miliknya sendiri
        if ($request->user()->isCashier()) {
            $query->where('cashier_id', $request->user()->id);
        }

        // Filter tanggal dari-sampai
        if ($request->filled('from')) {
            $query->whereDate('transaction_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('transaction_date', '<=', $request->to);
        }

        // Filter metode bayar
        if ($request->filled('method') && in_array($request->method, ['cash', 'qris'])) {
            $query->where('payment_method', $request->method);
        }

        // Filter kasir (admin only)
        if ($request->filled('cashier_id') && $request->user()->isAdmin()) {
            $query->where('cashier_id', $request->cashier_id);
        }

        $transactions = $query->withCount('items')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($t) => [
                'id'               => $t->id,
                'transaction_code' => $t->transaction_code,
                'transaction_date' => $t->transaction_date->format('d/m/Y'),
                'cashier_name'     => $t->cashier->name ?? '-',
                'payment_method'   => $t->payment_method,
                'grand_total'      => $t->grand_total,
                'items_count'      => $t->items_count,
            ]);

        // Daftar kasir untuk filter (admin only)
        $cashiers = $request->user()->isAdmin()
            ? User::where('role', 'cashier')->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('transactions/Index', [
            'transactions' => $transactions,
            'cashiers'     => $cashiers,
            'filters'      => $request->only('from', 'to', 'method', 'cashier_id'),
        ]);
    }

    /**
     * Form transaksi baru — kirim data layanan dari DB.
     */
    public function create(): Response
    {
        $categories = ServiceCategory::with(['services' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($cat) => [
                'id'       => $cat->id,
                'name'     => $cat->name,
                'services' => $cat->services->map(fn ($s) => [
                    'id'    => $s->id,
                    'name'  => $s->name,
                    'price' => $s->price,
                ])->values(),
            ])->values();

        return Inertia::render('transactions/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Simpan transaksi baru.
     */
    public function store(StoreTransactionRequest $request, StoreTransactionAction $action): RedirectResponse
    {
        $transaction = $action->execute($request->user(), $request->validated());

        return redirect()
            ->to(route('transactions.show', $transaction) . '?print=1')
            ->with('success', 'Transaksi berhasil disimpan. Halaman struk siap dicetak.');
    }

    /**
     * Detail transaksi / struk.
     */
    public function show(Request $request, Transaction $transaction): Response
    {
        // Kasir hanya bisa lihat struk miliknya sendiri
        if ($request->user()->isCashier() && $transaction->cashier_id !== $request->user()->id) {
            abort(403, 'Anda tidak memiliki akses ke transaksi ini.');
        }

        $transaction->load(['cashier', 'items']);

        return Inertia::render('transactions/Show', [
            'transaction' => [
                'id'               => $transaction->id,
                'transaction_code' => $transaction->transaction_code,
                'transaction_date' => $transaction->transaction_date->format('d/m/Y'),
                'transaction_time' => $transaction->created_at->format('H:i'),
                'cashier_name'     => $transaction->cashier->name,
                'payment_method'   => $transaction->payment_method,
                'subtotal'         => $transaction->subtotal,
                'grand_total'      => $transaction->grand_total,
                'notes'            => $transaction->notes,
                'items'            => $transaction->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'service_name' => $item->service_name,
                    'qty'          => $item->qty,
                    'unit_price'   => $item->unit_price,
                    'subtotal'     => $item->subtotal,
                ]),
            ],
            // 'store' dihilangkan — sudah dishare global via HandleInertiaRequests
        ]);
    }
}
