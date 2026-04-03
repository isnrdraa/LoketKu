<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        // Default: bulan berjalan
        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();

        // ─── Ringkasan periode ───────────────────────────────────────────────
        $transactions = Transaction::whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->with('items')
            ->get();

        $expenses = Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->get();

        $summary = [
            'total_revenue'      => $transactions->sum('grand_total'),
            'total_transactions' => $transactions->count(),
            'total_cash'         => $transactions->where('payment_method', 'cash')->sum('grand_total'),
            'total_qris'         => $transactions->where('payment_method', 'qris')->sum('grand_total'),
            'total_expenses'     => $expenses->sum('amount'),
            'net'                => $transactions->sum('grand_total') - $expenses->sum('amount'),
        ];

        // ─── Rekap harian ───────────────────────────────────────────────────
        $daily = Transaction::selectRaw(
            'transaction_date,
             COUNT(*) as tx_count,
             SUM(grand_total) as revenue,
             SUM(CASE WHEN payment_method = \'cash\' THEN grand_total ELSE 0 END) as cash,
             SUM(CASE WHEN payment_method = \'qris\' THEN grand_total ELSE 0 END) as qris'
        )
            ->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('transaction_date')
            ->orderByDesc('transaction_date')
            ->get()
            ->map(fn ($row) => [
                'date'     => Carbon::parse($row->transaction_date)->format('d/m/Y'),
                'tx_count' => $row->tx_count,
                'revenue'  => (int) $row->revenue,
                'cash'     => (int) $row->cash,
                'qris'     => (int) $row->qris,
            ]);

        // Gabung dengan pengeluaran harian
        $expensesByDate = $expenses->groupBy(fn ($e) => Carbon::parse($e->expense_date)->format('d/m/Y'))
            ->map(fn ($group) => $group->sum('amount'));

        $daily = $daily->map(fn ($row) => [
            ...$row,
            'expenses' => (int) ($expensesByDate[$row['date']] ?? 0),
            'net'      => (int) ($row['revenue'] - ($expensesByDate[$row['date']] ?? 0)),
        ]);

        // ─── Top layanan ────────────────────────────────────────────────────
        $topServices = TransactionItem::selectRaw(
            'service_name,
             SUM(qty) as total_qty,
             SUM(subtotal) as total_revenue'
        )
            ->whereHas('transaction', fn ($q) =>
                $q->whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
            )
            ->groupBy('service_name')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'service_name'  => $row->service_name,
                'total_qty'     => $row->total_qty,
                'total_revenue' => (int) $row->total_revenue,
            ]);

        // ─── Rekap pengeluaran per kategori ─────────────────────────────────
        $expenseByCategory = $expenses->groupBy('category')
            ->map(fn ($group, $cat) => [
                'category' => $cat,
                'total'    => (int) $group->sum('amount'),
                'count'    => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        return Inertia::render('admin/reports/Index', [
            'summary'            => $summary,
            'daily'              => $daily->values(),
            'top_services'       => $topServices,
            'expense_by_category'=> $expenseByCategory,
            'filters'            => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
        ]);
    }
}
