<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    // Daftar kategori pengeluaran umum
    public const CATEGORIES = [
        'Operasional',
        'Kebersihan',
        'Perlengkapan',
        'Perawatan',
        'Gaji',
        'Promosi',
        'Listrik/Air',
        'Lainnya',
    ];

    public function index(Request $request): Response
    {
        $query = Expense::with('creator:id,name')
            ->orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter tanggal
        if ($request->has('date') && $request->date) {
            $query->whereDate('expense_date', $request->date);
        }

        $expenses = $query->get(['id', 'expense_date', 'category', 'description', 'amount', 'created_by']);

        return Inertia::render('admin/expenses/Index', [
            'expenses'   => $expenses,
            'categories' => self::CATEGORIES,
            'filters'    => $request->only('date'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/expenses/Form', [
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'category'     => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
            'amount'       => 'required|numeric|min:1',
        ]);

        Expense::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function edit(Expense $expense): Response
    {
        return Inertia::render('admin/expenses/Form', [
            'expense'    => $expense->only('id', 'expense_date', 'category', 'description', 'amount'),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'category'     => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
            'amount'       => 'required|numeric|min:1',
        ]);

        $expense->update($data);

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil diperbarui.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran berhasil dihapus.');
    }
}
