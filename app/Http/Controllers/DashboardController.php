<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = now()->toDateString();
        $user  = $request->user();

        // ─── Statistik hari ini ─────────────────────────────────────────────
        $txQuery = Transaction::whereDate('transaction_date', $today);
        if ($user->isCashier()) {
            $txQuery->where('cashier_id', $user->id);
        }

        $todayRevenue      = (int) $txQuery->sum('grand_total');
        $todayTransactions = (int) $txQuery->count();
        $todayCash         = (int) (clone $txQuery)->where('payment_method', 'cash')->sum('grand_total');
        $todayQris         = (int) (clone $txQuery)->where('payment_method', 'qris')->sum('grand_total');
        $todayExpenses     = $user->isAdmin()
            ? (int) Expense::whereDate('expense_date', $today)->sum('amount')
            : 0;

        // ─── Grafik 7 hari (admin only) ─────────────────────────────────────
        $last7Days = [];
        if ($user->isAdmin()) {
            for ($i = 6; $i >= 0; $i--) {
                $date     = Carbon::today()->subDays($i);
                $revenue  = (int) Transaction::whereDate('transaction_date', $date)->sum('grand_total');
                $expenses = (int) Expense::whereDate('expense_date', $date)->sum('amount');

                $last7Days[] = [
                    'date'     => $date->format('d/m'),
                    'revenue'  => $revenue,
                    'expenses' => $expenses,
                    'net'      => $revenue - $expenses,
                ];
            }
        }

        // ─── Top 5 layanan hari ini ─────────────────────────────────────────
        $topServicesToday = TransactionItem::selectRaw(
            'service_name, SUM(qty) as total_qty, SUM(subtotal) as total_revenue'
        )
            ->whereHas('transaction', fn ($q) => $q->whereDate('transaction_date', $today))
            ->groupBy('service_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'name'    => $r->service_name,
                'qty'     => $r->total_qty,
                'revenue' => (int) $r->total_revenue,
            ]);

        return Inertia::render('Dashboard', [
            'stats' => [
                'today_revenue'      => $todayRevenue,
                'today_transactions' => $todayTransactions,
                'today_cash'         => $todayCash,
                'today_qris'         => $todayQris,
                'today_expenses'     => $todayExpenses,
                'today_net'          => $todayRevenue - $todayExpenses,
            ],
            'last_7_days'       => $last7Days,
            'top_services_today'=> $topServicesToday,
        ]);
    }
}
